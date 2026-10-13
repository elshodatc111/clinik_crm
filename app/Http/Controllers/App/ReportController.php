<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Support\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $r)
    {
        try {
            $from = Carbon::parse($r->query('from', now()->startOfMonth()->toDateString()))->startOfDay();
            $to = Carbon::parse($r->query('to', now()->toDateString()))->endOfDay();
        } catch (\Throwable $e) {
            $from = now()->startOfMonth();
            $to = now()->endOfDay();
        }
        $cid = Tenant::id();

        $payments = DB::table('payments')->where('clinic_id', $cid)->whereBetween('paid_at', [$from, $to]);

        $byMethod = (clone $payments)->selectRaw('method, COUNT(*) as c, SUM(amount) as s')->groupBy('method')->get();
        $daily = (clone $payments)->selectRaw('DATE(paid_at) as d, SUM(amount) as s')->groupBy('d')->orderBy('d')->get();

        $byDoctor = DB::table('payments')
            ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->leftJoin('users', 'users.id', '=', 'invoices.doctor_id')
            ->where('payments.clinic_id', $cid)->whereBetween('payments.paid_at', [$from, $to])
            ->selectRaw("COALESCE(users.name, '—') as doctor, SUM(payments.amount) as s, COUNT(DISTINCT invoices.id) as c")
            ->groupBy('doctor')->orderByDesc('s')->get();

        $byService = DB::table('invoice_items')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->where('invoice_items.clinic_id', $cid)->where('invoices.status', '!=', 'canceled')
            ->whereBetween('invoices.created_at', [$from, $to])
            ->selectRaw('invoice_items.name as name, SUM(invoice_items.qty) as qty, SUM(invoice_items.qty * invoice_items.price) as s')
            ->groupBy('invoice_items.name')->orderByDesc('s')->limit(30)->get();

        $debtors = DB::table('invoices')
            ->join('patients', 'patients.id', '=', 'invoices.patient_id')
            ->where('invoices.clinic_id', $cid)->whereIn('invoices.status', ['unpaid', 'partial'])
            ->selectRaw('invoices.id, invoices.number, patients.last_name, patients.first_name, patients.phone, (invoices.total - invoices.discount - invoices.paid) as debt')
            ->orderByDesc('debt')->limit(30)->get();

        $bySource = Patient::query()->whereBetween('created_at', [$from, $to])
            ->selectRaw("COALESCE(NULLIF(source, ''), '—') as src, COUNT(*) as c")->groupBy('src')->orderByDesc('c')->get();

        return view('app.reports', [
            'from' => $from, 'to' => $to,
            'total' => (int) $byMethod->sum('s'),
            'byMethod' => $byMethod, 'daily' => $daily, 'byDoctor' => $byDoctor, 'byService' => $byService,
            'debtors' => $debtors, 'bySource' => $bySource,
            'newPatients' => Patient::whereBetween('created_at', [$from, $to])->count(),
            'visits' => \App\Models\Visit::whereBetween('visited_at', [$from, $to])->count(),
        ]);
    }
}
