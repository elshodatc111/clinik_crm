<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\PayrollAdjustment;
use App\Models\Payment;
use App\Models\User;
use App\Support\Tenant;
use Carbon\Carbon;

/**
 * Xodimlar ish haqini hisoblash. Yangi turlar qo'shish uchun types() va calc() ni kengaytirish kifoya.
 */
class PayrollService
{
    public static function types(): array
    {
        return [
            'none' => 'Belgilanmagan',
            'monthly' => 'Oylik (belgilangan)',
            'daily' => 'Kunlik',
            'percent' => 'Foiz (tushumdan)',
            'mixed' => 'Aralash (oylik + foiz)',
        ];
    }

    public static function validPeriod(?string $p): string
    {
        return $p && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $p) ? $p : now()->format('Y-m');
    }

    /** Oy ichidagi ish kunlari (yakshanbasiz). */
    public static function planDays(string $period): int
    {
        $d = Carbon::createFromFormat('Y-m-d', $period.'-01')->startOfDay();
        $n = 0;
        for ($i = 0, $days = $d->daysInMonth; $i < $days; $i++) {
            if (! $d->copy()->addDays($i)->isSunday()) {
                $n++;
            }
        }

        return $n;
    }

    /** Davrdagi tushum (to'lovlar summasi). */
    public static function revenue(User $u, string $period): int
    {
        $start = Carbon::createFromFormat('Y-m-d', $period.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $q = Payment::query()
            ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->where('invoices.status', '!=', 'canceled')
            ->whereBetween('payments.paid_at', [$start, $end]);
        if (($u->pay_scope ?? 'own') !== 'clinic') {
            $q->where('invoices.doctor_id', $u->id);
        }

        return (int) $q->sum('payments.amount');
    }

    /** Hisoblash (saqlamaydi). */
    public static function calc(string $type, int $base, float $percent, int $planDays, int $workDays, int $revenue): array
    {
        $baseAmount = 0;
        $percentAmount = 0;
        if ($type === 'monthly' || $type === 'mixed') {
            $baseAmount = $planDays > 0 ? (int) round($base * min($workDays, $planDays) / $planDays) : 0;
        }
        if ($type === 'daily') {
            $baseAmount = $base * $workDays;
        }
        if ($type === 'percent' || $type === 'mixed') {
            $percentAmount = (int) round($revenue * $percent / 100);
        }

        return ['base_amount' => $baseAmount, 'percent_amount' => $percentAmount];
    }

    /** Barcha xodimlar uchun davr qatorlarini yaratadi/yangilaydi (to'langanlari o'zgarmaydi). */
    public static function sync(string $period): \Illuminate\Support\Collection
    {
        $plan = self::planDays($period);
        $adj = PayrollAdjustment::where('period', $period)->get()->groupBy('user_id');
        $existing = Payroll::where('period', $period)->get()->keyBy('user_id');
        $staff = User::clinicStaff()->where('is_active', true)->where('pay_type', '!=', 'none')->orderBy('name')->get();
        $rows = collect();

        foreach ($staff as $u) {
            $row = $existing->get($u->id);
            if ($row && $row->status === 'paid') {
                $rows->push($row->setRelation('user', $u));
                continue;
            }
            $a = $adj->get($u->id, collect());
            $work = $row ? (int) $row->work_days : $plan;
            $rev = in_array($u->pay_type, ['percent', 'mixed'], true) ? self::revenue($u, $period) : 0;
            $c = self::calc($u->pay_type, (int) $u->pay_base, (float) $u->pay_percent, $plan, $work, $rev);
            $bonus = (int) $a->where('kind', 'bonus')->sum('amount');
            $penalty = (int) $a->where('kind', 'penalty')->sum('amount');
            $advance = (int) $a->where('kind', 'advance')->sum('amount');
            $net = max(0, $c['base_amount'] + $c['percent_amount'] + $bonus - $penalty - $advance);
            $data = [
                'pay_type' => $u->pay_type, 'base' => (int) $u->pay_base, 'percent' => (float) $u->pay_percent,
                'plan_days' => $plan, 'work_days' => $work,
                'base_amount' => $c['base_amount'], 'percent_amount' => $c['percent_amount'],
                'bonus' => $bonus, 'penalty' => $penalty, 'advance' => $advance, 'net' => $net,
            ];
            if ($row) {
                $row->update($data);
            } else {
                $row = Payroll::create($data + ['user_id' => $u->id, 'period' => $period, 'status' => 'draft', 'clinic_id' => Tenant::id()]);
            }
            $rows->push($row->setRelation('user', $u));
        }

        return $rows;
    }
}
