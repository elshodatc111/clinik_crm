@extends('layouts.app')
@section('title', __('Obuna va balans'))
@section('content')
@php($colors = ['trial' => 'blue', 'active' => 'green', 'view_only' => 'orange', 'blocked' => 'red', 'suspended' => 'slate'])
<div class="grid lg:grid-cols-3 gap-4">
    <div class="card p-5">
        <div class="text-xs text-slate-500">{{ __('Balans') }}</div>
        <div class="text-3xl font-extrabold mt-1">{{ money($clinic->balance) }}</div>
        <div class="mt-3"><x-badge :color="$colors[$clinic->status] ?? 'slate'">{{ $clinic->statusLabel() }}</x-badge></div>
        @if($clinic->status === 'trial')
            <div class="text-sm text-slate-600 mt-2">{{ __('Demo tugashi') }}: {{ $clinic->trial_ends_at?->format('d.m.Y') }} ({{ __(':n kun qoldi', ['n' => $clinic->trialDaysLeft()]) }})</div>
        @elseif($clinic->paid_until)
            <div class="text-sm text-slate-600 mt-2">{{ __('Obuna tugashi') }}: {{ $clinic->paid_until->format('d.m.Y') }}</div>
        @endif
        @if($clinic->status === 'blocked' && $clinic->blocked_at)
            <div class="text-sm text-red-600 mt-2">{{ __("Ma'lumotlar o'chiriladi:") }} {{ $clinic->blocked_at->copy()->addDays(30)->format('d.m.Y') }}</div>
        @endif
        <div class="text-sm text-slate-600 mt-3">{{ __('Bemorlar') }}: <b>{{ $count }}</b> @if($plan)· {{ $plan->name }}: <b>{{ money($price) }}</b>/{{ __('oy') }}@endif</div>
    </div>

    <div class="card p-5 lg:col-span-2">
        <div class="font-semibold mb-3">{{ __("Balansni to'ldirish (Payme)") }}</div>
        @if(! $paymeReady)<div class="mb-3 rounded bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm px-3 py-2">{{ __("To'lov tizimi hali sozlanmagan.") }}</div>@endif
        <form method="POST" action="{{ route('app.billing.topup') }}" x-data="{ amount: {{ max(10000, $clinic->status === 'trial' ? max(0, $setupFee - $clinic->balance) : max(0, $price - $clinic->balance)) }} }" class="space-y-3">@csrf
            <div class="flex flex-wrap gap-2">
                @foreach([100000, 500000, 1000000, 2000000, 5000000] as $q)<button type="button" class="btn btn-sm" @click="amount = {{ $q }}">{{ number_format($q, 0, '.', ' ') }}</button>@endforeach
            </div>
            <div class="flex gap-2 max-w-md"><input type="number" name="amount" x-model="amount" min="1000" class="input"><button class="btn btn-primary whitespace-nowrap">{{ __("Payme orqali to'lash") }}</button></div>
            <p class="text-xs text-slate-500">{{ __("Oylik obuna to'lovi balansdan avtomatik yechiladi. Balansda mablag' bo'lishini oldindan ta'minlang.") }}</p>
        </form>
    </div>
</div>

@if($clinic->status === 'trial')
<div class="card p-5 mt-4 border-blue-200">
    <div class="font-semibold">{{ __("Obunaga o'tish") }}</div>
    <p class="text-sm text-slate-600 mt-1">{{ __("Bir martalik ulanish to'lovi:") }} <b>{{ money($setupFee) }}</b>. {{ __("To'lovdan keyin birinchi oy bepul.") }}</p>
    @if($clinic->balance >= $setupFee)
        <form method="POST" action="{{ route('app.billing.subscribe') }}" class="mt-3" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__('Balansdan ulanish to\'lovi yechiladi. Davom etasizmi?')) }})">@csrf<button class="btn btn-primary">{{ __("Obunaga o'tish (balansdan yechish)") }}</button></form>
    @else
        <p class="mt-3 text-sm text-orange-700">{{ __("Balansda mablag' yetarli emas. Yetishmayotgan summa:") }} <b>{{ money($setupFee - $clinic->balance) }}</b></p>
    @endif
</div>
@endif

@if(in_array($clinic->status, ['view_only', 'blocked']))
<div class="card p-5 mt-4 border-orange-200">
    <p class="text-sm">{{ __("Obunani tiklash uchun balansda kamida bir oylik to'lov bo'lishi kerak:") }} <b>{{ money($price) }}</b>. {{ __("Balans to'ldirilishi bilan obuna avtomatik tiklanadi.") }}</p>
    <a href="{{ route('app.export.patients') }}" class="btn btn-sm mt-3">{{ __("Bemorlar ro'yxatini yuklab olish (Excel/CSV)") }}</a>
</div>
@endif

<div class="grid lg:grid-cols-2 gap-4 mt-4">
    <div class="card overflow-hidden">
        <div class="px-4 py-3 font-semibold">{{ __('Tariflar') }}</div>
        <table class="w-full">@foreach($plans as $p)
            <tr class="{{ $plan && $plan->id === $p->id ? 'bg-blue-50' : '' }}"><td class="td">{{ $p->name }}</td><td class="td">{{ $p->rangeLabel() }} {{ __('bemor') }}</td><td class="td text-right font-medium">{{ money($p->monthly_price) }}</td></tr>
        @endforeach</table>
    </div>
    <div class="card overflow-hidden">
        <div class="px-4 py-3 font-semibold">{{ __('Balans harakatlari') }}</div>
        <table class="w-full">@forelse($transactions as $t)
            <tr><td class="td whitespace-nowrap">{{ $t->created_at?->format('d.m.Y H:i') }}</td><td class="td text-slate-500">{{ $t->description }}</td><td class="td text-right {{ $t->amount < 0 ? 'text-red-600' : 'text-green-700' }}">{{ ($t->amount > 0 ? '+' : '').number_format($t->amount, 0, '.', ' ') }}</td></tr>
        @empty<tr><td class="td text-slate-500">—</td></tr>@endforelse</table>
    </div>
</div>
@endsection
