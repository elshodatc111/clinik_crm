@extends('layouts.app')
@section('title', __('Kunlik kassa'))
@section('content')
@php
    $methods = \App\Models\Invoice::methods();
@endphp
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div class="flex items-center gap-2">
        <a class="btn btn-sm" href="{{ route('app.cash.index', ['date' => $prev]) }}">←</a>
        <form method="GET" class="flex gap-2"><input type="date" name="date" value="{{ $date }}" class="input !py-2" onchange="this.form.submit()"></form>
        <a class="btn btn-sm" href="{{ route('app.cash.index', ['date' => $next]) }}">→</a>
        @unless($isToday)<a class="btn btn-sm" href="{{ route('app.cash.index') }}">{{ __('Bugun') }}</a>@endunless
    </div>
    <div class="flex items-center gap-2">
        @if($closing)<x-badge color="slate">{{ __('Kun yopilgan') }}</x-badge>@else<x-badge color="green">{{ __('Kun ochiq') }}</x-badge>@endif
        <a class="btn" target="_blank" href="{{ route('app.cash.print', ['date' => $date]) }}"><x-icon name="printer" class="w-4 h-4" />{{ __('Chop etish') }}</a>
    </div>
</div>

<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <x-stat icon="trending-up" tone="green" :label="__('Kunlik tushum (jami)')" :value="money($total)" :hint="__('Naqd').': '.money($cashIn).' · '.__('Naqdsiz').': '.money($nonCash)" />
    <x-stat icon="banknote" tone="amber" :label="__('Chiqimlar (naqd)')" :value="money($cashOut)" :hint="$nonCashOut ? __('Naqdsiz chiqim').': '.money($nonCashOut) : null" />
    <x-stat icon="wallet" tone="slate" :label="__('Kun boshidagi qoldiq')" :value="money($opening)" />
    <x-stat icon="receipt" tone="blue" :label="__('Kassada bo\'lishi kerak (naqd)')" :value="money($expected)" />
</div>

@php
    $mc = [
        'cash' => ['Naqd', 'banknote', 'bg-emerald-50 text-emerald-600'],
        'card' => ['Karta (terminal)', 'credit-card', 'bg-blue-50 text-blue-600'],
        'payme' => ['Payme', 'smartphone', 'bg-cyan-50 text-cyan-600'],
        'click' => ['Click', 'smartphone', 'bg-sky-50 text-sky-600'],
        'transfer' => ["Bank o'tkazmasi", 'building', 'bg-violet-50 text-violet-600'],
        'other' => ['Boshqa', 'wallet', 'bg-slate-100 text-slate-600'],
    ];
@endphp
<div class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-700"><x-icon name="wallet" class="w-4 h-4" />{{ __("To'lov usullari bo'yicha tushum") }}</div>
<div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3 mb-6">
    @foreach($mc as $k => [$name, $ic, $cls])
        <div class="card p-4 flex items-center gap-3 {{ ($byMethod[$k] ?? 0) ? '' : 'opacity-60' }}">
            <span class="h-11 w-11 rounded-xl grid place-items-center {{ $cls }}"><x-icon :name="$ic" class="w-5 h-5" /></span>
            <div class="min-w-0 flex-1">
                <div class="text-xs text-slate-500">{{ __($name) }}@if($k === 'payme') <span class="text-[10px]">({{ __('onlayn qabul ham') }})</span>@endif</div>
                <div class="text-lg font-bold text-slate-900 truncate">{{ money($byMethod[$k] ?? 0) }}</div>
            </div>
            <div class="text-right text-xs text-slate-500">{{ $byMethodCount[$k] ?? 0 }} {{ __('ta') }}@if($total > 0)<div class="font-semibold text-slate-700">{{ round((($byMethod[$k] ?? 0) / $total) * 100) }}%</div>@endif</div>
        </div>
    @endforeach
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="card overflow-x-auto">
            <div class="px-5 py-4 font-semibold border-b border-slate-100 flex items-center gap-2"><x-icon name="receipt" class="w-4 h-4" />{{ __('Kunlik to\'lovlar') }} <span class="text-xs text-slate-500 font-normal">({{ $payments->count() }})</span></div>
            <table class="w-full">
                <thead><tr><th class="th">{{ __('Vaqt') }}</th><th class="th">{{ __('Bemor') }}</th><th class="th">{{ __('Hisob-faktura') }}</th><th class="th">{{ __('Usul') }}</th><th class="th">{{ __('Kassir') }}</th><th class="th text-right">{{ __('Summa') }}</th></tr></thead>
                @forelse($payments as $p)
                    <tr><td class="td">{{ $p->paid_at->format('H:i') }}</td><td class="td">{{ $p->ownerPatient()?->full_name ?? '—' }}</td>
                        <td class="td">@if($p->invoice)<a class="text-blue-600" href="{{ route('app.invoices.show', $p->invoice) }}">{{ $p->invoice->number }}</a>@else<x-badge color="blue">{{ __('Avans') }}</x-badge>@endif</td>
                        <td class="td">{{ __($methods[$p->method] ?? $p->method) }}</td><td class="td">{{ $p->cashier?->name ?? '—' }}</td><td class="td text-right font-medium">{{ money($p->amount) }}</td></tr>
                @empty
                    <tr><td class="td text-center text-slate-500 py-8" colspan="6">{{ __('Bu kunda to\'lovlar yo\'q.') }}</td></tr>
                @endforelse
            </table>
        </div>

        <div class="card overflow-x-auto">
            <div class="px-5 py-4 font-semibold border-b border-slate-100 flex items-center gap-2"><x-icon name="banknote" class="w-4 h-4" />{{ __('Kassa harakatlari (xarajat, maosh, avans)') }}</div>
            <table class="w-full">
                <thead><tr><th class="th">{{ __('Turi') }}</th><th class="th">{{ __('Izoh') }}</th><th class="th">{{ __('Usul') }}</th><th class="th text-right">{{ __('Summa') }}</th><th class="th"></th></tr></thead>
                @forelse($moves as $m)
                    <tr><td class="td">{{ __(\App\Models\CashMovement::categories()[$m->category] ?? $m->category) }}</td><td class="td">{{ $m->note }}</td>
                        <td class="td">{{ ['cash' => __('Naqd'), 'card' => __('Karta'), 'transfer' => __('Bank o\'tkazmasi')][$m->method] ?? $m->method }}</td>
                        <td class="td text-right font-medium {{ $m->direction === 'in' ? 'text-emerald-600' : 'text-red-600' }}">{{ $m->direction === 'in' ? '+' : '−' }} {{ money($m->amount) }}</td>
                        <td class="td text-right">@unless($closing)@unless($m->payroll_id)<form method="POST" action="{{ route('app.cash.destroy', $m) }}" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__('O\'chirasizmi?')) }})">@csrf @method('DELETE')<button class="text-red-500" title="{{ __('O\'chirish') }}"><x-icon name="trash" class="w-4 h-4" /></button></form>@endunless @endunless</td></tr>
                @empty
                    <tr><td class="td text-center text-slate-500 py-8" colspan="5">{{ __('Harakatlar yo\'q.') }}</td></tr>
                @endforelse
            </table>
        </div>
    </div>

    <div class="space-y-6">
        <div class="card p-5">
            <div class="font-semibold mb-3">{{ __("To'lov usullari ulushi") }}</div>
            @if($byMethod->count())<div class="h-56"><canvas id="cCashMethod"></canvas></div>@else<div class="text-sm text-slate-500 py-8 text-center">—</div>@endif
            @if($byHour->count())
            <div class="font-semibold mt-5 mb-2">{{ __('Soatlar bo\'yicha tushum') }}</div>
            <div class="h-44"><canvas id="cCashHour"></canvas></div>
            @endif
            @if($byCashier->count() > 0)
            <div class="font-semibold mt-4 mb-2">{{ __('Kassirlar bo\'yicha') }}</div>
            @foreach($byCashier as $k => $v)<div class="flex justify-between py-1.5 text-sm border-b border-slate-100 last:border-0"><span>{{ $k }}</span><b>{{ money($v) }}</b></div>@endforeach
            @endif
        </div>

        @unless($closing)
        <form method="POST" action="{{ route('app.cash.store') }}" class="card p-5 space-y-3">
            @csrf <input type="hidden" name="date" value="{{ $date }}">
            <div class="font-semibold flex items-center gap-2"><x-icon name="plus" class="w-4 h-4" />{{ __('Xarajat / kirim qo\'shish') }}</div>
            <div class="grid grid-cols-2 gap-2">
                <select name="direction" class="input"><option value="out">{{ __('Chiqim') }}</option><option value="in">{{ __('Kirim') }}</option></select>
                <select name="category" class="input">@foreach(\App\Models\CashMovement::categories() as $k => $l)<option value="{{ $k }}">{{ __($l) }}</option>@endforeach</select>
                <select name="method" class="input"><option value="cash">{{ __('Naqd') }}</option><option value="card">{{ __('Karta') }}</option><option value="transfer">{{ __('Bank o\'tkazmasi') }}</option></select>
                <input type="number" name="amount" min="1" class="input" placeholder="{{ __('Summa') }}" required>
            </div>
            <input type="text" name="note" class="input" placeholder="{{ __('Izoh (masalan: suv, tozalash vositalari)') }}">
            <button class="btn btn-primary w-full">{{ __('Qo\'shish') }}</button>
        </form>

        <form method="POST" action="{{ route('app.cash.close') }}" class="card p-5 space-y-3" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__('Kunni yopishni tasdiqlaysizmi?')) }})">
            @csrf <input type="hidden" name="date" value="{{ $date }}">
            <div class="font-semibold flex items-center gap-2"><x-icon name="check" class="w-4 h-4" />{{ __('Kunni yopish') }}</div>
            <p class="text-xs text-slate-500">{{ __('Kutilayotgan naqd') }}: <b>{{ money($expected) }}</b>. {{ __('Kassadagi haqiqiy naqd pulni sanab kiriting.') }}</p>
            <input type="number" name="counted_cash" min="0" value="{{ $expected > 0 ? $expected : 0 }}" class="input" required>
            <input type="text" name="note" class="input" placeholder="{{ __('Izoh') }}">
            <button class="btn btn-primary w-full">{{ __('Kunni yopish') }}</button>
        </form>
        @else
        <div class="card p-5">
            <div class="font-semibold mb-3 flex items-center gap-2"><x-icon name="check" class="w-4 h-4" />{{ __('Kun yopilgan') }}</div>
            <div class="text-sm space-y-1.5">
                <div class="flex justify-between"><span>{{ __('Kutilgan naqd') }}</span><b>{{ money($closing->expected_cash) }}</b></div>
                <div class="flex justify-between"><span>{{ __('Sanalgan naqd') }}</span><b>{{ money($closing->counted_cash) }}</b></div>
                <div class="flex justify-between"><span>{{ __('Farq') }}</span><b class="{{ $closing->difference == 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $closing->difference > 0 ? '+' : '' }}{{ money($closing->difference) }}</b></div>
                @if($closing->note)<div class="text-slate-500 text-xs">{{ $closing->note }}</div>@endif
                <div class="text-xs text-slate-500">{{ $closing->closed_at?->format('d.m.Y H:i') }}</div>
            </div>
            @if(auth()->user()->hasRole('clinic_admin'))
            <form method="POST" action="{{ route('app.cash.reopen') }}" class="mt-3" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__('Kunni qayta ochasizmi?')) }})">@csrf <input type="hidden" name="date" value="{{ $date }}"><button class="btn btn-sm">{{ __('Qayta ochish') }}</button></form>
            @endif
        </div>
        @endunless

        @if($recentClosings->isNotEmpty())
        <div class="card p-5">
            <div class="font-semibold mb-2">{{ __('So\'nggi yopilgan kunlar') }}</div>
            @foreach($recentClosings as $c)
                <a href="{{ route('app.cash.index', ['date' => $c->date->toDateString()]) }}" class="flex justify-between py-1.5 text-sm border-b border-slate-100 last:border-0 hover:text-blue-600"><span>{{ $c->date->format('d.m.Y') }}</span><span class="{{ $c->difference == 0 ? '' : 'text-red-600' }}">{{ money($c->counted_cash) }}</span></a>
            @endforeach
        </div>
        @endif
    </div>
</div>
@include('partials.charts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const names = @json(collect($mc)->map(fn ($v) => __($v[0]))->all());
    KC.donut('cCashMethod', @json($byMethod->keys()->map(fn ($k) => __($mc[$k][0] ?? $k))->values()), @json($byMethod->values()->map(fn ($v) => (int) $v)));
    KC.bar('cCashHour', @json($byHour->keys()->map(fn ($h) => $h.':00')->values()), @json($byHour->values()->map(fn ($v) => (int) $v)), @json(__('Tushum')));
});
</script>
@endsection
