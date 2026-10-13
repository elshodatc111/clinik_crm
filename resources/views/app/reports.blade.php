@extends('layouts.app')
@section('title', __('Hisobotlar'))
@section('content')
@php
    $methods = \App\Models\Invoice::methods();
    $card = 'card overflow-hidden';
@endphp
<form method="GET" class="card p-4 flex flex-wrap items-end gap-3 mb-5 no-print">
    <div><label class="block text-xs font-medium text-slate-500 mb-1">{{ __('Dan') }}</label><input type="date" name="from" value="{{ $from->toDateString() }}" class="input"></div>
    <div><label class="block text-xs font-medium text-slate-500 mb-1">{{ __('Gacha') }}</label><input type="date" name="to" value="{{ $to->toDateString() }}" class="input"></div>
    <button class="btn btn-primary"><x-icon name="search" class="w-4 h-4" />{{ __("Ko'rsatish") }}</button>
    <button type="button" class="btn" onclick="window.print()"><x-icon name="printer" class="w-4 h-4" />{{ __('Chop etish') }}</button>
    <div class="flex gap-1 ml-auto text-xs">
        <a class="btn btn-sm" href="?from={{ now()->toDateString() }}&to={{ now()->toDateString() }}">{{ __('Bugun') }}</a>
        <a class="btn btn-sm" href="?from={{ now()->subDays(6)->toDateString() }}&to={{ now()->toDateString() }}">7 {{ __('kun') }}</a>
        <a class="btn btn-sm" href="?from={{ now()->startOfMonth()->toDateString() }}&to={{ now()->toDateString() }}">{{ __('Shu oy') }}</a>
        <a class="btn btn-sm" href="?from={{ now()->subDays(89)->toDateString() }}&to={{ now()->toDateString() }}">90 {{ __('kun') }}</a>
    </div>
</form>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <x-stat icon="banknote" tone="green" :label="__('Tushum')" :value="money($total)" />
    <x-stat icon="stethoscope" tone="blue" :label="__('Tashriflar')" :value="$visits" />
    <x-stat icon="user-plus" tone="violet" :label="__('Yangi bemorlar')" :value="$newPatients" />
    <x-stat icon="alert" tone="red" :label="__('Qarzdor hisoblar')" :value="$debtors->count()" />
</div>

<div class="grid lg:grid-cols-3 gap-4 mt-4">
    <div class="{{ $card }} lg:col-span-2">
        <div class="section-title"><x-icon name="trending-up" class="w-4 h-4 text-brand" />{{ __('Kunlik tushum') }}</div>
        <div class="p-5">@if($daily->count())<div class="h-72"><canvas id="cDaily"></canvas></div>@else<div class="text-sm text-slate-500 py-16 text-center">{{ __("Ma'lumot yo'q") }}</div>@endif</div>
    </div>
    <div class="{{ $card }}">
        <div class="section-title"><x-icon name="credit-card" class="w-4 h-4 text-brand" />{{ __("To'lov turlari bo'yicha") }}</div>
        <div class="p-5">@if($byMethod->count())<div class="h-72"><canvas id="cMethod"></canvas></div>@else<div class="text-sm text-slate-500 py-16 text-center">{{ __("Ma'lumot yo'q") }}</div>@endif</div>
    </div>
    <div class="{{ $card }} lg:col-span-2">
        <div class="section-title"><x-icon name="stethoscope" class="w-4 h-4 text-brand" />{{ __("Shifokorlar bo'yicha tushum") }}</div>
        <div class="p-5">@if($byDoctor->count())<div class="h-72"><canvas id="cDoctor"></canvas></div>@else<div class="text-sm text-slate-500 py-16 text-center">{{ __("Ma'lumot yo'q") }}</div>@endif</div>
    </div>
    <div class="{{ $card }}">
        <div class="section-title"><x-icon name="users" class="w-4 h-4 text-brand" />{{ __('Reklama manbalari (yangi bemorlar)') }}</div>
        <div class="p-5">@if($bySource->count())<div class="h-72"><canvas id="cSource"></canvas></div>@else<div class="text-sm text-slate-500 py-16 text-center">{{ __("Ma'lumot yo'q") }}</div>@endif</div>
    </div>
    <div class="{{ $card }} lg:col-span-3">
        <div class="section-title"><x-icon name="tag" class="w-4 h-4 text-brand" />{{ __("Xizmatlar bo'yicha") }} <span class="text-xs font-normal text-slate-400">TOP 8</span></div>
        <div class="p-5">@if($byService->count())<div class="h-80"><canvas id="cService"></canvas></div>@else<div class="text-sm text-slate-500 py-16 text-center">{{ __("Ma'lumot yo'q") }}</div>@endif</div>
    </div>
</div>

<div class="{{ $card }} mt-4">
    <div class="section-title"><x-icon name="alert" class="w-4 h-4 text-red-500" />{{ __('Qarzdorlar') }}</div>
    <div class="overflow-x-auto"><table class="w-full">
        @forelse($debtors as $m)<tr><td class="td"><a class="link" href="{{ route('app.invoices.show', $m->id) }}">{{ $m->number }}</a></td><td class="td">{{ $m->last_name }} {{ $m->first_name }}<div class="text-xs text-slate-500">{{ phone_fmt($m->phone) }}</div></td><td class="td text-right text-red-600 font-semibold">{{ money($m->debt) }}</td></tr>
        @empty<tr><td class="td text-slate-500">{{ __("Qarzdorlar yo'q") }}</td></tr>@endforelse
    </table></div>
</div>
@endsection
@push('scripts')
@include('partials.charts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    KC.area('cDaily', @json($daily->map(fn ($m) => \Carbon\Carbon::parse($m->d)->format('d.m'))->values()), @json($daily->pluck('s')->map(fn ($v) => (int) $v)->values()), @json(__('Tushum')));
    KC.donut('cMethod', @json($byMethod->map(fn ($m) => __($methods[$m->method] ?? $m->method))->values()), @json($byMethod->pluck('s')->map(fn ($v) => (int) $v)->values()));
    KC.bar('cDoctor', @json($byDoctor->pluck('doctor')->values()), @json($byDoctor->pluck('s')->map(fn ($v) => (int) $v)->values()), @json(__('Tushum')));
    KC.donut('cSource', @json($bySource->pluck('src')->values()), @json($bySource->pluck('c')->map(fn ($v) => (int) $v)->values()), false);
    KC.bar('cService', @json($byService->take(8)->pluck('name')->values()), @json($byService->take(8)->pluck('s')->map(fn ($v) => (int) $v)->values()), @json(__('Tushum')), true);
});
</script>
@endpush
