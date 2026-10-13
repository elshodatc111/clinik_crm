<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\CashClosing;
use App\Models\CashMovement;
use App\Models\CreditApplication;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Services\PatientBalance;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Bemor avansi (oldindan to'lov): qabul qilish, hisob-fakturani avansdan yopish, qaytarish. */
class PatientBalanceController extends Controller
{
    protected function dayClosed(): bool
    {
        return CashClosing::whereDate('date', now()->toDateString())->exists();
    }

    public function deposit(Request $r, Patient $patient)
    {
        $d = $r->validate([
            'amount' => 'required|integer|min:1|max:100000000000',
            'method' => 'required|in:'.implode(',', array_keys(Invoice::methods())),
            'note' => 'nullable|string|max:200',
        ]);
        if ($this->dayClosed()) {
            return back()->with('error', __("Bugungi kassa yopilgan. Avval kassani qayta oching."));
        }
        Payment::create([
            'invoice_id' => null, 'patient_id' => $patient->id, 'amount' => $d['amount'], 'method' => $d['method'],
            'cashier_id' => $r->user()->id, 'paid_at' => now(), 'note' => $d['note'] ?: __('Avans'),
        ]);

        return back()->with('success', __('Avans qabul qilindi.'));
    }

    public function refund(Request $r, Patient $patient)
    {
        $d = $r->validate([
            'amount' => 'required|integer|min:1|max:100000000000',
            'method' => 'required|in:cash,card,transfer',
            'note' => 'nullable|string|max:200',
        ]);
        if ($this->dayClosed()) {
            return back()->with('error', __("Bugungi kassa yopilgan. Avval kassani qayta oching."));
        }
        $err = DB::transaction(function () use ($d, $patient, $r) {
            $bal = PatientBalance::forPatient($patient->id);
            if ($d['amount'] > $bal['credit']) {
                return __("Qaytariladigan summa avansdan oshib ketdi. Avans: :s", ['s' => Tenant::money($bal['credit'])]);
            }
            $mv = CashMovement::create([
                'date' => now()->toDateString(), 'direction' => 'out', 'category' => 'refund', 'method' => $d['method'],
                'amount' => $d['amount'], 'note' => __('Avans qaytarildi').': '.$patient->full_name, 'created_by' => $r->user()->id,
            ]);
            CreditApplication::create([
                'patient_id' => $patient->id, 'kind' => 'refund', 'amount' => $d['amount'], 'cash_movement_id' => $mv->id,
                'note' => $d['note'] ?? null, 'created_by' => $r->user()->id,
            ]);

            return null;
        });

        return back()->with($err ? 'error' : 'success', $err ?: __('Avans qaytarildi.'));
    }

    public function applyCredit(Request $r, Invoice $invoice)
    {
        $d = $r->validate(['amount' => 'required|integer|min:1']);
        $err = DB::transaction(function () use ($invoice, $d, $r) {
            $inv = Invoice::whereKey($invoice->id)->lockForUpdate()->first();
            if ($inv->status === 'canceled') {
                return __("Bekor qilingan hisob-fakturaga to'lov qabul qilinmaydi.");
            }
            $credit = PatientBalance::forPatient($inv->patient_id)['credit'];
            $max = min($credit, $inv->remaining());
            if ($d['amount'] > $max) {
                return __("Summa :s dan oshmasligi kerak (avans va qoldiq bo'yicha).", ['s' => Tenant::money($max)]);
            }
            CreditApplication::create([
                'patient_id' => $inv->patient_id, 'invoice_id' => $inv->id, 'kind' => 'invoice', 'amount' => $d['amount'],
                'note' => __('Avansdan yopildi'), 'created_by' => $r->user()->id,
            ]);
            $inv->paid += $d['amount'];
            $inv->status = $inv->remaining() === 0 ? 'paid' : 'partial';
            $inv->save();

            return null;
        });

        return back()->with($err ? 'error' : 'success', $err ?: __('Hisob-faktura avansdan yopildi.'));
    }
}
