<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BalanceTransaction;
use App\Models\Clinic;
use App\Models\Patient;

class DashboardController extends Controller
{
    public function index()
    {
        $byStatus = Clinic::query()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $mrr = Clinic::where('status', Clinic::ACTIVE)->with('plan')->get()->sum(fn ($c) => $c->plan?->monthly_price ?? 0);

        $from = now()->subDays(29)->startOfDay();
        $tp = BalanceTransaction::where('type', 'topup')->where('created_at', '>=', $from)->selectRaw('DATE(created_at) as d, SUM(amount) as s')->groupBy('d')->pluck('s', 'd');
        $chartLabels = [];
        $chartTopups = [];
        for ($i = 0; $i < 30; $i++) {
            $day = $from->copy()->addDays($i);
            $chartLabels[] = $day->format('d.m');
            $chartTopups[] = (int) ($tp[$day->toDateString()] ?? 0);
        }

        return view('admin.dashboard', [
            'chartLabels' => $chartLabels, 'chartTopups' => $chartTopups,
            'byStatus' => $byStatus,
            'total' => Clinic::count(),
            'pendingPayments' => \App\Services\ManualPaymentService::pendingCount(),
            'mrr' => $mrr,
            'new30' => Clinic::where('created_at', '>=', now()->subDays(30))->count(),
            'topups30' => BalanceTransaction::where('type', 'topup')->where('created_at', '>=', now()->subDays(30))->sum('amount'),
            'patients' => Patient::withoutGlobalScopes()->count(),
            'trials' => Clinic::where('status', Clinic::TRIAL)->orderBy('trial_ends_at')->limit(8)->get(),
            'problem' => Clinic::whereIn('status', [Clinic::VIEW_ONLY, Clinic::BLOCKED])->orderBy('paid_until')->limit(8)->get(),
        ]);
    }
}
