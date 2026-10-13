@extends('layouts.booking')
@section('title', __('Qabul talonchasi'))
@section('content')
<div class="max-w-md mx-auto" x-data="{ status: '{{ $b->status }}', init() { if (this.status === 'pending') { const t = setInterval(async () => { try { const r = await fetch('{{ route('booking.status', [$clinic->slug, $b->token]) }}'); const j = await r.json(); if (j.status !== this.status) { clearInterval(t); location.reload(); } } catch (e) {} }, 3000); } } }">
    @if(session('error'))<div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>@endif

    @if($b->status === 'confirmed')
        <div class="card overflow-hidden">
            <div class="hero-grad text-white p-6 text-center">
                <span class="mx-auto h-12 w-12 rounded-full bg-white/20 grid place-items-center"><x-icon name="check" class="w-6 h-6" /></span>
                <div class="mt-3 text-lg font-bold">{{ __('Qabulingiz tasdiqlandi!') }}</div>
                <div class="text-white/80 text-sm">{{ __("To'lov qabul qilindi") }}</div>
            </div>
            <div class="p-6 text-center">
                <div class="text-xs uppercase tracking-wider text-slate-500">{{ __('Navbat raqami') }}</div>
                <div class="text-6xl font-extrabold text-brand leading-none mt-1">{{ $queue }}</div>
            </div>
            <dl class="px-6 pb-6 space-y-3 text-sm border-t border-dashed border-slate-200 pt-5">
                <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Bemor') }}</dt><dd class="font-medium text-right">{{ $b->full_name }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Sana va vaqt') }}</dt><dd class="font-medium">{{ $b->starts_at->format('d.m.Y H:i') }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Shifokor') }}</dt><dd class="font-medium text-right">{{ $b->doctor?->name }}@if($b->doctor?->room) · {{ __('xona') }} {{ $b->doctor->room }}@endif</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Xizmat') }}</dt><dd class="font-medium text-right">{{ $b->service?->name }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __("To'langan") }}</dt><dd class="font-bold">{{ money($b->amount) }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Manzil') }}</dt><dd class="text-right">{{ $clinic->address }}</dd></div>
            </dl>
            <div class="p-4 border-t border-slate-100 flex gap-2 no-print"><button onclick="window.print()" class="btn flex-1"><x-icon name="printer" class="w-4 h-4" />{{ __('Chop etish') }}</button></div>
        </div>
        <p class="text-xs text-slate-500 text-center mt-4">{{ __('Iltimos, qabuldan 10 daqiqa oldin keling va shu talonchani ko\'rsating.') }}</p>

    @elseif($b->status === 'pending')
        <div class="card p-8 text-center">
            <span class="mx-auto h-14 w-14 rounded-2xl bg-amber-50 text-amber-600 grid place-items-center"><x-icon name="clock" class="w-7 h-7" /></span>
            <h1 class="mt-4 text-xl font-bold text-slate-900">{{ __("To'lov kutilmoqda") }}</h1>
            <p class="text-slate-500 mt-2 text-sm">{{ $b->starts_at->format('d.m.Y H:i') }} · {{ $b->doctor?->name }}<br>{{ money($b->amount) }}</p>
            @if($payUrl)<a href="{{ $payUrl }}" class="btn btn-primary mt-5 !py-3 w-full"><x-icon name="credit-card" class="w-4 h-4" />{{ __("Payme orqali to'lash") }}</a>@endif
            @if($sandbox)
                <form method="POST" action="{{ route('booking.simulate', [$clinic->slug, $b->token]) }}" class="mt-3">@csrf
                    <button class="btn w-full">{{ __("Sinov rejimi: to'lovsiz tasdiqlash") }}</button>
                    <p class="text-xs text-slate-500 mt-2">{{ __("Payme ulanmagan. Bu tugma faqat sinov (APP_DEBUG) rejimida ko'rinadi.") }}</p>
                </form>
            @endif
            <p class="text-xs text-slate-500 mt-4">{{ __("Vaqt :m daqiqa davomida band qilib turiladi.", ['m' => max(1, (int) now()->diffInMinutes($b->hold_expires_at, false))]) }}</p>
        </div>

    @else
        <div class="card p-8 text-center">
            <span class="mx-auto h-14 w-14 rounded-2xl bg-red-50 text-red-600 grid place-items-center"><x-icon name="x" class="w-7 h-7" /></span>
            <h1 class="mt-4 text-xl font-bold text-slate-900">{{ $b->status === 'refunded' ? __("To'lov qaytarilgan") : __('Bron muddati tugadi') }}</h1>
            <p class="text-slate-500 mt-2 text-sm">{{ __("Qaytadan urinib ko'ring.") }}</p>
            <a href="{{ route('booking.show', $clinic->slug) }}" class="btn btn-primary mt-5">{{ __('Yangi bron') }}</a>
        </div>
    @endif
</div>
@endsection
