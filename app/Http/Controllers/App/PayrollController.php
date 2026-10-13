<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\CashClosing;
use App\Models\CashMovement;
use App\Models\Payroll;
use App\Models\PayrollAdjustment;
use App\Models\User;
use App\Services\PayrollService;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    public function index(Request $r)
    {
        $period = PayrollService::validPeriod($r->query('period'));
        $rows = PayrollService::sync($period);
        $adj = PayrollAdjustment::where('period', $period)->latest()->get()->groupBy('user_id');

        return view('app.payroll.index', [
            'period' => $period,
            'rows' => $rows,
            'adj' => $adj,
            'totals' => [
                'net' => $rows->sum('net'),
                'paid' => $rows->where('status', 'paid')->sum('net'),
                'due' => $rows->where('status', '!=', 'paid')->sum('net'),
                'advance' => $rows->sum('advance'),
            ],
            'prev' => \Carbon\Carbon::createFromFormat('Y-m-d', $period.'-01')->subMonth()->format('Y-m'),
            'next' => \Carbon\Carbon::createFromFormat('Y-m-d', $period.'-01')->addMonth()->format('Y-m'),
        ]);
    }

    public function print(Request $r)
    {
        $period = PayrollService::validPeriod($r->query('period'));

        return view('app.payroll.print', ['period' => $period, 'rows' => PayrollService::sync($period)]);
    }

    protected function row(Payroll $payroll): Payroll
    {
        abort_unless((int) $payroll->clinic_id === (int) Tenant::id(), 404);

        return $payroll;
    }

    public function days(Request $r, Payroll $payroll)
    {
        $this->row($payroll);
        if ($payroll->status === 'paid') {
            return back()->with('error', __("To'langan qatorni o'zgartirib bo'lmaydi."));
        }
        $d = $r->validate(['work_days' => 'required|integer|min:0|max:31']);
        $payroll->update(['work_days' => $d['work_days']]);

        return back()->with('success', __('Saqlandi.'));
    }

    public function adjust(Request $r, User $user)
    {
        abort_unless((int) $user->clinic_id === (int) Tenant::id(), 404);
        $d = $r->validate([
            'period' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'kind' => 'required|in:bonus,penalty,advance',
            'amount' => 'required|integer|min:1|max:100000000000',
            'note' => 'nullable|string|max:200',
        ]);
        if (Payroll::where('user_id', $user->id)->where('period', $d['period'])->where('status', 'paid')->exists()) {
            return back()->with('error', __("Bu davr uchun ish haqi allaqachon to'langan."));
        }
        if ($d['kind'] === 'advance' && $this->dayClosed(now()->toDateString())) {
            return back()->with('error', __("Bugungi kassa yopilgan. Avval kassani qayta oching."));
        }
        DB::transaction(function () use ($d, $user, $r) {
            $mvId = null;
            if ($d['kind'] === 'advance') {
                $mvId = CashMovement::create([
                    'date' => now()->toDateString(), 'direction' => 'out', 'category' => 'advance', 'method' => 'cash',
                    'amount' => $d['amount'], 'note' => __('Avans').': '.$user->name, 'user_id' => $user->id, 'created_by' => $r->user()->id,
                ])->id;
            }
            PayrollAdjustment::create($d + ['user_id' => $user->id, 'cash_movement_id' => $mvId, 'created_by' => $r->user()->id]);
        });

        return back()->with('success', __('Saqlandi.'));
    }

    public function destroyAdjustment(PayrollAdjustment $adjustment)
    {
        abort_unless((int) $adjustment->clinic_id === (int) Tenant::id(), 404);
        if (Payroll::where('user_id', $adjustment->user_id)->where('period', $adjustment->period)->where('status', 'paid')->exists()) {
            return back()->with('error', __("Bu davr uchun ish haqi allaqachon to'langan."));
        }
        if ($adjustment->cash_movement_id) {
            $mv = CashMovement::find($adjustment->cash_movement_id);
            if ($mv && $this->dayClosed($mv->date->toDateString())) {
                return back()->with('error', __("Avans berilgan kun kassasi yopilgan. Avval kassani qayta oching."));
            }
            $mv?->delete();
        }
        $adjustment->delete();

        return back()->with('success', __("O'chirildi."));
    }

    public function pay(Request $r, Payroll $payroll)
    {
        $this->row($payroll);
        if ($payroll->status === 'paid') {
            return back()->with('error', __("Allaqachon to'langan."));
        }
        $d = $r->validate(['method' => 'required|in:cash,card,transfer']);
        if ($this->dayClosed(now()->toDateString())) {
            return back()->with('error', __("Bugungi kassa yopilgan. Avval kassani qayta oching."));
        }
        // eng so'nggi hisob-kitob (qo'shimchalar bilan)
        $fresh = PayrollService::sync($payroll->period)->firstWhere('id', $payroll->id) ?? $payroll;
        if ($fresh->net <= 0) {
            return back()->with('error', __("To'lanadigan summa 0."));
        }
        DB::transaction(function () use ($fresh, $d, $r) {
            $mv = CashMovement::create([
                'date' => now()->toDateString(), 'direction' => 'out', 'category' => 'salary', 'method' => $d['method'],
                'amount' => $fresh->net, 'note' => __('Ish haqi').' '.$fresh->period.': '.$fresh->user?->name,
                'user_id' => $fresh->user_id, 'payroll_id' => $fresh->id, 'created_by' => $r->user()->id,
            ]);
            $fresh->update(['status' => 'paid', 'paid_at' => now(), 'paid_by' => $r->user()->id]);
        });

        return back()->with('success', __("Ish haqi to'landi va kassaga chiqim sifatida yozildi."));
    }

    protected function dayClosed(string $date): bool
    {
        return CashClosing::whereDate('date', $date)->exists();
    }
}
