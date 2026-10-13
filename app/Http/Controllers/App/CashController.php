<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\CashClosing;
use App\Models\CashMovement;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CashController extends Controller
{
    protected function date(Request $r): string
    {
        $d = $r->query('date');
        try {
            return $d ? Carbon::createFromFormat('Y-m-d', $d)->toDateString() : now()->toDateString();
        } catch (\Throwable) {
            return now()->toDateString();
        }
    }

    protected function paymentsOn(string $date)
    {
        return Payment::with(['invoice.patient', 'patient', 'cashier'])
            ->whereDate('paid_at', $date)
            ->orderBy('paid_at')
            ->get();
    }

    /** [from, to) oralig'idagi sof naqd pul harakati (yopilmagan kunlar uchun). */
    protected function cashFlow(?string $afterDate, string $beforeDate): int
    {
        $pay = Payment::where('method', 'cash')->whereDate('paid_at', '<', $beforeDate);
        $mv = CashMovement::where('method', 'cash')->whereDate('date', '<', $beforeDate);
        if ($afterDate) {
            $pay->whereDate('paid_at', '>', $afterDate);
            $mv->whereDate('date', '>', $afterDate);
        }
        $in = (int) $pay->sum('amount') + (int) (clone $mv)->where('direction', 'in')->sum('amount');
        $out = (int) (clone $mv)->where('direction', 'out')->sum('amount');

        return $in - $out;
    }

    public function opening(string $date): int
    {
        $last = CashClosing::whereDate('date', '<', $date)->orderByDesc('date')->first();

        return (int) ($last?->counted_cash ?? 0) + $this->cashFlow($last?->date?->toDateString(), $date);
    }

    protected function summary(string $date): array
    {
        $payments = $this->paymentsOn($date);
        $moves = CashMovement::whereDate('date', $date)->orderBy('id')->get();
        $byMethod = $payments->groupBy('method')->map->sum('amount');
        $byMethodCount = $payments->groupBy('method')->map->count();
        $byHour = $payments->groupBy(fn ($p) => $p->paid_at->format('H'))->map->sum('amount')->sortKeys();
        $cashIn = (int) ($byMethod['cash'] ?? 0);
        $nonCash = (int) $payments->sum('amount') - $cashIn;
        $mvIn = (int) $moves->where('direction', 'in')->where('method', 'cash')->sum('amount');
        $cashOut = (int) $moves->where('direction', 'out')->where('method', 'cash')->sum('amount');
        $nonCashOut = (int) $moves->where('direction', 'out')->where('method', '!=', 'cash')->sum('amount');
        $opening = $this->opening($date);

        return [
            'payments' => $payments, 'moves' => $moves, 'byMethod' => $byMethod, 'byMethodCount' => $byMethodCount, 'byHour' => $byHour,
            'opening' => $opening, 'cashIn' => $cashIn, 'mvIn' => $mvIn, 'nonCash' => $nonCash,
            'cashOut' => $cashOut, 'nonCashOut' => $nonCashOut,
            'expected' => $opening + $cashIn + $mvIn - $cashOut,
            'total' => (int) $payments->sum('amount'),
            'byCashier' => $payments->groupBy(fn ($p) => $p->cashier?->name ?? '—')->map->sum('amount'),
        ];
    }

    public function index(Request $r)
    {
        $date = $this->date($r);
        $c = Carbon::parse($date);

        return view('app.cash.index', $this->summary($date) + [
            'date' => $date,
            'closing' => CashClosing::whereDate('date', $date)->first(),
            'prev' => $c->copy()->subDay()->toDateString(),
            'next' => $c->copy()->addDay()->toDateString(),
            'isToday' => $date === now()->toDateString(),
            'recentClosings' => CashClosing::orderByDesc('date')->limit(7)->get(),
        ]);
    }

    public function print(Request $r)
    {
        $date = $this->date($r);

        return view('app.cash.print', $this->summary($date) + ['date' => $date, 'closing' => CashClosing::whereDate('date', $date)->first()]);
    }

    protected function closed(string $date): bool
    {
        return CashClosing::whereDate('date', $date)->exists();
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'date' => 'required|date_format:Y-m-d',
            'direction' => 'required|in:in,out',
            'category' => 'required|in:'.implode(',', array_keys(CashMovement::categories())),
            'method' => 'required|in:cash,card,transfer',
            'amount' => 'required|integer|min:1|max:100000000000',
            'note' => 'nullable|string|max:200',
        ]);
        if ($this->closed($d['date'])) {
            return back()->with('error', __("Bu kun kassasi yopilgan. Avval qayta oching."));
        }
        CashMovement::create($d + ['created_by' => $r->user()->id]);

        return redirect()->route('app.cash.index', ['date' => $d['date']])->with('success', __('Saqlandi.'));
    }

    public function destroy(CashMovement $movement)
    {
        if ($this->closed($movement->date->toDateString())) {
            return back()->with('error', __("Bu kun kassasi yopilgan. Avval qayta oching."));
        }
        if ($movement->payroll_id) {
            return back()->with('error', __("Ish haqi chiqimini Ish haqi bo'limidan boshqarish kerak."));
        }
        if (in_array($movement->category, ['advance'], true) && \App\Models\PayrollAdjustment::where('cash_movement_id', $movement->id)->exists()) {
            return back()->with('error', __("Avansni Ish haqi bo'limidan o'chiring."));
        }
        $movement->delete();

        return back()->with('success', __("O'chirildi."));
    }

    public function close(Request $r)
    {
        $d = $r->validate([
            'date' => 'required|date_format:Y-m-d',
            'counted_cash' => 'required|integer|min:0|max:100000000000',
            'note' => 'nullable|string|max:200',
        ]);
        if ($this->closed($d['date'])) {
            return back()->with('error', __('Kun allaqachon yopilgan.'));
        }
        if ($d['date'] > now()->toDateString()) {
            return back()->with('error', __("Kelajak kunini yopib bo'lmaydi."));
        }
        $s = $this->summary($d['date']);
        CashClosing::create([
            'date' => $d['date'], 'opening_cash' => $s['opening'],
            'cash_in' => $s['cashIn'] + $s['mvIn'], 'non_cash_in' => $s['nonCash'], 'cash_out' => $s['cashOut'],
            'expected_cash' => $s['expected'], 'counted_cash' => $d['counted_cash'],
            'difference' => $d['counted_cash'] - $s['expected'],
            'note' => $d['note'] ?? null, 'closed_by' => $r->user()->id, 'closed_at' => now(),
        ]);

        return back()->with('success', __('Kun yopildi.'));
    }

    public function reopen(Request $r)
    {
        abort_unless($r->user()->hasRole(User::ADMIN), 403);
        $d = $r->validate(['date' => 'required|date_format:Y-m-d']);
        // faqat eng oxirgi yopilgan kunni qayta ochish mumkin (zanjir buzilmasligi uchun)
        $latest = CashClosing::orderByDesc('date')->first();
        if (! $latest || $latest->date->toDateString() !== $d['date']) {
            return back()->with('error', __("Faqat eng oxirgi yopilgan kunni qayta ochish mumkin."));
        }
        $latest->delete();

        return back()->with('success', __('Kun qayta ochildi.'));
    }
}
