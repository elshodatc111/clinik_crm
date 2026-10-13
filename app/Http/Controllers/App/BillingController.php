<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
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
}
