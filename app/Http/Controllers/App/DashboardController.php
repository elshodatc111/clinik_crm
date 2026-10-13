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
            ->when($user->role === 'doctor', fn ($q) => $q->where('doctor_id', $user->id))
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
            ->when($user->role === 'doctor', fn ($q) => $q->where('doctor_id', $user->id))
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        return view('app.dashboard', [
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
