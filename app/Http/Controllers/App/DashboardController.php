<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Services\BillingService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $r)
    {
        $user = $r->user();
        $apps = Appointment::with(['patient', 'doctor', 'service'])->whereDate('starts_at', today())
            ->when($user->isScopedDoctor(), fn ($q) => $q->where('doctor_id', $user->id))
            ->orderBy('starts_at');

        $count = Patient::count();

        $from = now()->subDays(13)->startOfDay();
        $rev = Payment::where('paid_at', '>=', $from)->selectRaw('DATE(paid_at) as d, SUM(amount) as s')->groupBy('d')->pluck('s', 'd');
        $chartLabels = [];
        $chartRevenue = [];
        for ($i = 0; $i < 14; $i++) {
            $day = $from->copy()->addDays($i);
            $chartLabels[] = $day->format('d.m');
            $chartRevenue[] = (int) ($rev[$day->toDateString()] ?? 0);
        }
        $statusCounts = Appointment::where('starts_at', '>=', now()->startOfMonth())
            ->when($user->isScopedDoctor(), fn ($q) => $q->where('doctor_id', $user->id))
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        // Klinika admini uchun tavsiyalar (kamchiliklar) — kuniga bir marta ilova ichida xabar ham yuboriladi
        $issues = [];
        if ($user->role === 'clinic_admin' && ($clinic = \App\Models\Clinic::find($user->clinic_id))) {
            $issues = \Illuminate\Support\Facades\Cache::remember('diag_issues_'.$clinic->id, 600, fn () => \App\Services\ClinicDiagnostics::run($clinic));
            if (\Illuminate\Support\Facades\Cache::add('diag_notified_'.$clinic->id, 1, now()->addDay())) {
                \App\Services\ClinicDiagnostics::notify($clinic, $issues);
            }
        }

        return view('app.dashboard', [
            'issues' => $issues,
            'chartLabels' => $chartLabels, 'chartRevenue' => $chartRevenue, 'statusCounts' => $statusCounts,
            'patients' => $count,
            'plan' => BillingService::planFor($count),
            'todayCount' => (clone $apps)->count(),
            'today' => $apps->limit(12)->get(),
            'revenueToday' => Payment::whereDate('paid_at', today())->sum('amount'),
            'revenueMonth' => Payment::where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
            'debt' => (int) Invoice::whereIn('status', ['unpaid', 'partial'])->selectRaw('COALESCE(SUM(total - discount - paid),0) as s')->value('s'),
        ]);
    }
}
