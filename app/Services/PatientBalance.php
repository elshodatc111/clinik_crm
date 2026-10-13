<?php

namespace App\Services;

use App\Models\CreditApplication;
use App\Models\Invoice;
use App\Models\Payment;

/**
 * Bemorning umumiy balansi.
 *  qarz   = bekor qilinmagan hisob-fakturalar qoldig'i yig'indisi
 *  avans  = hisob-fakturasiz qabul qilingan to'lovlar − avansdan sarflanganlar (hisob yopish, qaytarish)
 *  balans = avans − qarz  (manfiy: qarz, musbat: ortiqcha to'lov)
 */
class PatientBalance
{
    /** @return array<int, array{debt:int,credit:int,balance:int}> */
    public static function forPatients(array $ids): array
    {
        $out = [];
        foreach ($ids as $id) {
            $out[(int) $id] = ['debt' => 0, 'credit' => 0, 'balance' => 0];
        }
        if (! $ids) {
            return $out;
        }

        Invoice::whereIn('patient_id', $ids)->where('status', '!=', 'canceled')->get(['patient_id', 'total', 'discount', 'paid'])
            ->each(function ($i) use (&$out) {
                $out[$i->patient_id]['debt'] += max(0, (int) $i->total - (int) $i->discount - (int) $i->paid);
            });
        Payment::whereNull('invoice_id')->whereIn('patient_id', $ids)->selectRaw('patient_id, SUM(amount) as s')->groupBy('patient_id')->get()
            ->each(function ($r) use (&$out) {
                $out[$r->patient_id]['credit'] += (int) $r->s;
            });
        CreditApplication::whereIn('patient_id', $ids)->selectRaw('patient_id, SUM(amount) as s')->groupBy('patient_id')->get()
            ->each(function ($r) use (&$out) {
                $out[$r->patient_id]['credit'] -= (int) $r->s;
            });

        foreach ($out as $id => $b) {
            $out[$id]['balance'] = $b['credit'] - $b['debt'];
        }

        return $out;
    }

    public static function forPatient(int $id): array
    {
        return self::forPatients([$id])[$id];
    }

    /** Klinika bo'yicha jami: qarz va avans. */
    public static function clinicTotals(): array
    {
        $debt = 0;
        Invoice::where('status', '!=', 'canceled')->whereIn('status', ['unpaid', 'partial'])->get(['total', 'discount', 'paid'])
            ->each(function ($i) use (&$debt) {
                $debt += max(0, (int) $i->total - (int) $i->discount - (int) $i->paid);
            });
        $credit = (int) Payment::whereNull('invoice_id')->sum('amount') - (int) CreditApplication::sum('amount');

        return ['debt' => $debt, 'credit' => max(0, $credit)];
    }
}
