<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\Plan;
use App\Services\BillingService;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function index()
    {
        return view('public.landing', [
            'plans' => Plan::where('is_active', true)->orderBy('sort')->orderBy('min_patients')->get(),
            'setupFee' => BillingService::setupFee(),
            'demoDays' => (int) \App\Models\Setting::get('demo_days', 14),
        ]);
    }

    public function offer()
    {
        return view('public.offer', ['offer' => Offer::active()]);
    }

    public function lang(Request $request, string $locale)
    {
        abort_unless(in_array($locale, ['uz', 'ru'], true), 404);
        session(['locale' => $locale]);
        if ($user = $request->user()) {
            $user->forceFill(['locale' => $locale])->saveQuietly();
        }

        return back();
    }
}
