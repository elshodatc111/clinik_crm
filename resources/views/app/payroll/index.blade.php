@extends('layouts.app')
@section('title', __('Ish haqi'))
@section('content')
@php($mn = ['01'=>'Yanvar','02'=>'Fevral','03'=>'Mart','04'=>'Aprel','05'=>'May','06'=>'Iyun','07'=>'Iyul','08'=>'Avgust','09'=>'Sentabr','10'=>'Oktabr','11'=>'Noyabr','12'=>'Dekabr'])
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div class="flex items-center gap-2">
        <a class="btn btn-sm" href="{{ route('app.payroll.index', ['period' => $prev]) }}">←</a>
        <div class="px-4 py-2 rounded-xl bg-white border border-slate-200 font-semibold text-slate-800">{{ __($mn[substr($period, 5, 2)]) }} {{ substr($period, 0, 4) }}</div>
        <a class="btn btn-sm" href="{{ route('app.payroll.index', ['period' => $next]) }}">→</a>
    </div>
    <div class="flex gap-2">
        <a class="btn" target="_blank" href="{{ route('app.payroll.print', ['period' => $period]) }}"><x-icon name="printer" class="w-4 h-4" />{{ __('Chop etish') }}</a>
        <a class="btn btn-primary" href="{{ route('app.staff.index') }}"><x-icon name="user" class="w-4 h-4" />{{ __('Xodimlar sozlamasi') }}</a>
    </div>
</div>

<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <x-stat icon="wallet" tone="blue" :label="__('Jami hisoblangan')" :value="money($totals['net'])" />
    <x-stat icon="check" tone="green" :label="__('To\'langan')" :value="money($totals['paid'])" />
    <x-stat icon="clock" tone="amber" :label="__('To\'lanishi kerak')" :value="money($totals['due'])" />
    <x-stat icon="banknote" tone="violet" :label="__('Berilgan avanslar')" :value="money($totals['advance'])" />
</div>

@if($rows->isEmpty())
    <div class="card p-8 text-center text-slate-500">
        <x-icon name="wallet" class="w-8 h-8 mx-auto mb-2 text-slate-400" />
        {{ __('Ish haqi belgilangan xodimlar yo\'q. Xodim kartasida "Ish haqi turi"ni tanlang.') }}
    </div>
@endif

<div class="space-y-4">
@foreach($rows as $p)
    @php($u = $p->user)
    @php($paid = $p->status === 'paid')
    <div class="card p-5" x-data="{ adj: false, pay: false }">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <span class="h-11 w-11 rounded-full bg-blue-50 text-blue-600 grid place-items-center font-bold shrink-0">{{ mb_substr($u->name, 0, 1) }}</span>
                <div class="min-w-0">
                    <div class="font-semibold text-slate-900 truncate">{{ $u->name }}</div>
                    <div class="text-xs text-slate-500">{{ $u->roleLabel() }} · {{ __(\App\Services\PayrollService::types()[$p->pay_type] ?? $p->pay_type) }}
                        @if(in_array($p->pay_type, ['monthly','daily','mixed'])) · {{ money($p->base) }}@endif
                        @if(in_array($p->pay_type, ['percent','mixed'])) · {{ rtrim(rtrim(number_format($p->percent, 2), '0'), '.') }}%@endif
                    </div>
                </div>
            </div>
            <div class="text-right">
                <div class="text-xs text-slate-500">{{ __('To\'lanadi') }}</div>
                <div class="text-2xl font-bold text-slate-900">{{ money($p->net) }}</div>
                @if($paid)<x-badge color="green">{{ __('To\'langan') }} · {{ $p->paid_at?->format('d.m.Y') }}</x-badge>@else<x-badge color="yellow">{{ __('Kutilmoqda') }}</x-badge>@endif
            </div>
        </div>

        <div class="mt-4 grid sm:grid-cols-2 lg:grid-cols-5 gap-3 text-sm">
            <div class="rounded-xl bg-slate-50 p-3"><div class="text-xs text-slate-500">{{ __('Asosiy qism') }}</div><div class="font-semibold">{{ money($p->base_amount) }}</div>
                @if(in_array($p->pay_type, ['monthly','daily','mixed']))
                    @if($paid)<div class="text-xs text-slate-500">{{ $p->work_days }} / {{ $p->plan_days }} {{ __('kun') }}</div>
                    @else
                    <form method="POST" action="{{ route('app.payroll.days', $p) }}" class="mt-1 flex items-center gap-1">@csrf @method('PUT')
                        <input type="number" name="work_days" value="{{ $p->work_days }}" min="0" max="31" class="input !py-1 !px-2 w-16 text-xs">
                        <span class="text-xs text-slate-500">/ {{ $p->plan_days }} {{ __('kun') }}</span>
                        <button class="btn btn-sm !px-2" title="{{ __('Saqlash') }}"><x-icon name="check" class="w-3.5 h-3.5" /></button>
                    </form>
                    @endif
                @endif
            </div>
            <div class="rounded-xl bg-slate-50 p-3"><div class="text-xs text-slate-500">{{ __('Foiz qismi') }}</div><div class="font-semibold">{{ money($p->percent_amount) }}</div></div>
            <div class="rounded-xl bg-emerald-50 p-3"><div class="text-xs text-emerald-700">{{ __('Mukofot') }}</div><div class="font-semibold text-emerald-700">+ {{ money($p->bonus) }}</div></div>
            <div class="rounded-xl bg-red-50 p-3"><div class="text-xs text-red-700">{{ __('Jarima') }}</div><div class="font-semibold text-red-700">− {{ money($p->penalty) }}</div></div>
            <div class="rounded-xl bg-amber-50 p-3"><div class="text-xs text-amber-700">{{ __('Avans') }}</div><div class="font-semibold text-amber-700">− {{ money($p->advance) }}</div></div>
        </div>

        @if(($adj[$u->id] ?? collect())->isNotEmpty())
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach($adj[$u->id] as $a)
                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 px-3 py-1 text-xs">
                    {{ ['bonus' => __('Mukofot'), 'penalty' => __('Jarima'), 'advance' => __('Avans')][$a->kind] }}: {{ money($a->amount) }} @if($a->note)<span class="text-slate-500">· {{ $a->note }}</span>@endif
                    @unless($paid)
                    <form method="POST" action="{{ route('app.payroll.adjustment.destroy', $a) }}" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__('O\'chirasizmi?')) }})" class="inline">@csrf @method('DELETE')<button class="text-red-500" title="{{ __('O\'chirish') }}"><x-icon name="x" class="w-3 h-3" /></button></form>
                    @endunless
                </span>
            @endforeach
        </div>
        @endif

        @unless($paid)
        <div class="mt-4 flex flex-wrap gap-2">
            <button type="button" class="btn btn-sm" @click="adj = !adj; pay = false"><x-icon name="plus" class="w-3.5 h-3.5" />{{ __('Mukofot / jarima / avans') }}</button>
            <button type="button" class="btn btn-sm btn-primary" @click="pay = !pay; adj = false"><x-icon name="banknote" class="w-3.5 h-3.5" />{{ __('Ish haqini to\'lash') }}</button>
        </div>
        <form method="POST" action="{{ route('app.payroll.adjust', $u) }}" x-show="adj" x-cloak class="mt-3 grid md:grid-cols-5 gap-2 items-end">
            @csrf <input type="hidden" name="period" value="{{ $period }}">
            <select name="kind" class="input"><option value="bonus">{{ __('Mukofot') }}</option><option value="penalty">{{ __('Jarima') }}</option><option value="advance">{{ __('Avans (kassadan beriladi)') }}</option></select>
            <input type="number" name="amount" min="1" class="input" placeholder="{{ __('Summa') }}" required>
            <input type="text" name="note" class="input md:col-span-2" placeholder="{{ __('Izoh') }}">
            <button class="btn btn-primary">{{ __('Qo\'shish') }}</button>
        </form>
        <form method="POST" action="{{ route('app.payroll.pay', $p) }}" x-show="pay" x-cloak class="mt-3 flex flex-wrap gap-2 items-end" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__('Ish haqi to\'langanini tasdiqlaysizmi?')) }})">
            @csrf
            <select name="method" class="input w-auto"><option value="cash">{{ __('Naqd (kassadan)') }}</option><option value="card">{{ __('Karta') }}</option><option value="transfer">{{ __('Bank o\'tkazmasi') }}</option></select>
            <button class="btn btn-primary">{{ __('To\'landi — tasdiqlash') }} ({{ money($p->net) }})</button>
        </form>
        @endunless
    </div>
@endforeach
</div>
@endsection
