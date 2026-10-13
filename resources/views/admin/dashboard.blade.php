@extends('layouts.app')
@section('title', __('Bosh sahifa'))
@section('content')
@php
    $colors = ['trial' => 'blue', 'active' => 'green', 'view_only' => 'orange', 'blocked' => 'red', 'suspended' => 'slate'];
    $hex = ['trial' => '#2563eb', 'active' => '#22c55e', 'view_only' => '#f97316', 'blocked' => '#ef4444', 'suspended' => '#94a3b8'];
    $icons = ['trial' => 'flask', 'active' => 'check', 'view_only' => 'eye', 'blocked' => 'shield', 'suspended' => 'clock'];
@endphp
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <x-stat icon="building" tone="blue" :label="__('Jami klinikalar')" :value="$total" />
    <x-stat icon="trending-up" tone="green" :label="__('Oylik tushum (MRR)')" :value="money($mrr)" />
    <x-stat icon="wallet" tone="violet" :label="__('Oxirgi 30 kunda to\'ldirilgan balans')" :value="money($topups30)" />
    <x-stat icon="user-plus" tone="cyan" :label="__('Yangi klinikalar (30 kun)')" :value="$new30" :hint="__('Tizimdagi jami bemorlar').': '.number_format($patients, 0, '.', ' ')" />
</div>

<div class="grid lg:grid-cols-3 gap-4 mt-5">
    <div class="card lg:col-span-2 overflow-hidden">
        <div class="section-title"><x-icon name="wallet" class="w-4 h-4 text-brand" />{{ __("Balans to'ldirish (30 kun)") }}</div>
        <div class="p-5"><div class="h-64"><canvas id="cTop"></canvas></div></div>
    </div>
    <div class="card overflow-hidden">
        <div class="section-title"><x-icon name="building" class="w-4 h-4 text-brand" />{{ __('Klinikalar holati') }}</div>
        <div class="p-5"><div class="h-64"><canvas id="cSt"></canvas></div></div>
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mt-5">
    @foreach(\App\Models\Clinic::statusLabels() as $k => $label)
        <a href="{{ route('admin.clinics.index', ['status' => $k]) }}" class="card p-4 transition hover:-translate-y-0.5 hover:shadow-md flex items-center justify-between">
            <div><x-badge :color="$colors[$k]">{{ __($label) }}</x-badge><div class="mt-2 text-2xl font-bold">{{ $byStatus[$k] ?? 0 }}</div></div>
            <x-icon :name="$icons[$k]" class="w-6 h-6 text-slate-300" />
        </a>
    @endforeach
</div>

<div class="grid lg:grid-cols-2 gap-4 mt-5">
    <div class="card overflow-hidden">
        <div class="section-title"><x-icon name="flask" class="w-4 h-4 text-brand" />{{ __('Demo tugashiga yaqin') }}</div>
        <table class="w-full"><tbody>
            @forelse($trials as $c)
                <tr><td class="td"><a class="link" href="{{ route('admin.clinics.show', $c) }}">{{ $c->name }}</a><div class="text-xs text-slate-500">{{ phone_fmt($c->phone) }}</div></td>
                    <td class="td text-right">{{ __(':n kun', ['n' => $c->trialDaysLeft()]) }}</td></tr>
            @empty<tr><td class="td text-slate-500">{{ __("Demo klinikalar yo'q") }}</td></tr>@endforelse
        </tbody></table>
    </div>
    <div class="card overflow-hidden">
        <div class="section-title"><x-icon name="alert" class="w-4 h-4 text-red-500" />{{ __("To'lov muammosi bor klinikalar") }}</div>
        <table class="w-full"><tbody>
            @forelse($problem as $c)
                <tr><td class="td"><a class="link" href="{{ route('admin.clinics.show', $c) }}">{{ $c->name }}</a><div class="text-xs text-slate-500">{{ phone_fmt($c->phone) }}</div></td>
                    <td class="td"><x-badge :color="$colors[$c->status]">{{ $c->statusLabel() }}</x-badge></td>
                    <td class="td text-right">{{ $c->paid_until?->format('d.m.Y') }}</td></tr>
            @empty<tr><td class="td text-slate-500">{{ __("Yo'q") }}</td></tr>@endforelse
        </tbody></table>
    </div>
</div>
@endsection
@push('scripts')
@include('partials.charts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    KC.bar('cTop', @json($chartLabels), @json($chartTopups), @json(__("To'ldirish")));
    @php $labels = \App\Models\Clinic::statusLabels(); $keys = collect(array_keys($labels))->filter(fn ($k) => ($byStatus[$k] ?? 0) > 0)->values(); @endphp
    KC.donut('cSt', @json($keys->map(fn ($k) => __($labels[$k]))), @json($keys->map(fn ($k) => (int) $byStatus[$k])), false, @json($keys->map(fn ($k) => $hex[$k]))); 
});
</script>
@endpush
