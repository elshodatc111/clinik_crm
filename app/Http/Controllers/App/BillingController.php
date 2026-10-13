<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\ManualPayment;
use App\Services\ManualPaymentService;
use App\Models\Setting;
use App\Models\TopupOrder;
use App\Services\BillingService;
use App\Services\PaymeService;
use App\Support\Tenant;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    protected function clinic(): Clinic
    {
        return Clinic::findOrFail(Tenant::id());
    }

    public function index()
    {
        $clinic = $this->clinic();
        $count = $clinic->patientCount();

        return view('app.billing', [
            'clinic' => $clinic,
            'count' => $count,
            'plan' => BillingService::planFor($count),
            'price' => BillingService::monthlyPrice($clinic),
            'setupFee' => BillingService::setupFee(),
            'transactions' => $clinic->balanceTransactions()->limit(30)->get(),
            'paymeReady' => (bool) Setting::get('payme_merchant_id'),
            'cardReady' => ManualPaymentService::enabled(),
            'card' => ManualPaymentService::card(),
            'manual' => ManualPayment::where('clinic_id', $clinic->id)->latest('id')->limit(10)->get(),
            'needed' => max(10000, $clinic->status === 'trial' ? max(0, BillingService::setupFee() - $clinic->balance) : max(0, BillingService::monthlyPrice($clinic) - $clinic->balance)),
            'plans' => \App\Models\Plan::where('is_active', true)->orderBy('sort')->orderBy('min_patients')->get(),
        ]);
    }

    public function topup(Request $r)
    {
        $d = $r->validate(['amount' => 'required|integer|min:1000|max:200000000']);
        if (! Setting::get('payme_merchant_id')) {
            return back()->with('error', __("To'lov tizimi hali sozlanmagan. Administrator bilan bog'laning."));
        }
        $order = TopupOrder::create(['clinic_id' => Tenant::id(), 'user_id' => $r->user()->id, 'amount' => $d['amount'], 'status' => 'new']);

        return redirect()->away(PaymeService::checkoutUrl($order, route('app.billing')));
    }

    public function subscribe()
    {
        $err = BillingService::subscribe($this->clinic());
        if ($err) {
            return back()->with('error', $err);
        }

        return redirect()->route('app.billing')->with('success', __("Obuna faollashtirildi. Birinchi oy bepul!"));
    }

    /** "To'lov qildim": chek rasmi bilan kartaga to'lov so'rovi. */
    public function manualStore(Request $r)
    {
        $d = $r->validateWithBag('manual', [
            'amount' => 'required|integer|min:1000|max:200000000',
            'paid_at' => 'required|date|before_or_equal:today|after_or_equal:'.today()->subDays(30)->toDateString(),
            'payer_card' => ['nullable', 'digits:4'],
            'note' => 'nullable|string|max:300',
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ], [
            'receipt.required' => __("To'lov chekining rasmini yuklang."), 'receipt.mimes' => __('Chek rasm (JPG, PNG, WEBP) yoki PDF bo\'lishi kerak.'), 'receipt.max' => __('Fayl hajmi 5 MB dan oshmasin.'),
            'paid_at.before_or_equal' => __("To'lov sanasi kelajakda bo'lishi mumkin emas."), 'paid_at.after_or_equal' => __("To'lov sanasi 30 kundan eski bo'lmasligi kerak."),
        ]);
        [$p, $err] = ManualPaymentService::submit($this->clinic(), $r->user(), $d, $r->file('receipt'));
        if ($err) {
            return back()->withInput()->with('error', $err);
        }

        return redirect()->route('app.billing')->with('success', __("To'lov cheki yuborildi. Tekshirilgach balansingiz to'ldiriladi va sizga xabar beriladi."));
    }

    protected function ownPayment(ManualPayment $payment): ManualPayment
    {
        abort_unless((int) $payment->clinic_id === (int) Tenant::id(), 404);

        return $payment;
    }

    public function manualReceipt(ManualPayment $payment)
    {
        return ManualPaymentService::receiptResponse($this->ownPayment($payment));
    }

    public function manualCancel(Request $r, ManualPayment $payment)
    {
        $ok = ManualPaymentService::cancelByClinic($this->ownPayment($payment), $r->user());

        return back()->with($ok ? 'success' : 'error', $ok ? __("To'lov so'rovi bekor qilindi.") : __("Bu to'lov allaqachon ko'rib chiqilgan."));
    }
}
