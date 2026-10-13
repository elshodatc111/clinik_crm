@extends('layouts.app')
@section('title', __('Bosh sahifa'))
@section('content')
@php
    $role = auth()->user()->effectiveRole();
    $money = in_array($role, ['clinic_admin', 'cashier']);
    $stColors = ['done' => 'green', 'canceled' => 'red', 'no_show' => 'red', 'in_progress' => 'blue', 'arrived' => 'yellow'];
@endphp
<div class="hero-grad rounded-2xl p-6 md:p-7 text-white flex flex-wrap items-center justify-between gap-4 shadow-lg">
    <div>
        <div class="text-white/70 text-sm">{{ now()->translatedFormat('d.m.Y') }}</div>
        <div class="text-2xl font-bold mt-1">{{ __('Xush kelibsiz') }}, {{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first() }}!</div>
        <div class="text-white/80 text-sm mt-1">{{ __('Bugungi qabullar') }}: <b>{{ $todayCount }}</b></div>
    </div>
    <div class="flex flex-wrap gap-2">
        @if(in_array($role, ['clinic_admin', 'receptionist']))<a href="{{ route('app.patients.create') }}" class="btn !bg-white !text-slate-800 border-transparent"><x-icon name="user-plus" class="w-4 h-4" />{{ __('Yangi bemor') }}</a>@endif
        @if(in_array($role, ['clinic_admin', 'receptionist', 'doctor']))<a href="{{ route('app.appointments.create') }}" class="btn !bg-white/15 !text-white border-white/20 hover:!bg-white/25"><x-icon name="calendar" class="w-4 h-4" />{{ __('Qabulga yozish') }}</a>@endif
        @if(in_array($role, ['clinic_admin', 'receptionist', 'cashier']))<a href="{{ route('app.invoices.create') }}" class="btn !bg-white/15 !text-white border-white/20 hover:!bg-white/25"><x-icon name="receipt" class="w-4 h-4" />{{ __('Hisob-faktura') }}</a>@endif
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-5">
    <x-stat icon="users" tone="blue" :label="__('Bemorlar')" :value="number_format($patients, 0, '.', ' ')" :hint="$plan?->name" />
    <x-stat icon="calendar" tone="violet" :label="__('Bugungi qabullar')" :value="$todayCount" />
    @if($money)
        <x-stat icon="banknote" tone="green" :label="__('Bugungi tushum')" :value="money($revenueToday)" :hint="__('Oy boshidan').': '.money($revenueMonth)" />
        <x-stat icon="alert" tone="red" :label="__('Qarzdorlik')" :value="money($debt)" />
    @endif
</div>

<div class="grid lg:grid-cols-3 gap-4 mt-5">
    @if($money)
    <div class="card lg:col-span-2 overflow-hidden">
        <div class="section-title"><x-icon name="trending-up" class="w-4 h-4 text-brand" />{{ __('Oxirgi 14 kunlik tushum') }}</div>
        <div class="p-5"><div class="h-64"><canvas id="cRev"></canvas></div></div>
    </div>
    @endif
    <div class="card overflow-hidden {{ $money ? '' : 'lg:col-span-1' }}">
        <div class="section-title"><x-icon name="activity" class="w-4 h-4 text-brand" />{{ __('Shu oy qabullar holati') }}</div>
        <div class="p-5">@if($statusCounts->sum())<div class="h-64"><canvas id="cStat"></canvas></div>@else<div class="text-sm text-slate-500 py-20 text-center">{{ __("Ma'lumot yo'q") }}</div>@endif</div>
    </div>
</div>

@if(in_array($role, ['clinic_admin', 'receptionist', 'doctor']))
<div class="card overflow-hidden mt-5">
    <div class="section-title"><x-icon name="clock" class="w-4 h-4 text-brand" />{{ __('Bugungi qabullar jadvali') }}</div>
    <div class="overflow-x-auto"><table class="w-full">
        @forelse($today as $a)
            <tr><td class="td whitespace-nowrap font-semibold text-slate-900">{{ $a->starts_at->format('H:i') }}</td>
                <td class="td"><a class="link" href="{{ route('app.patients.show', $a->patient_id) }}">{{ $a->patient?->full_name }}</a></td>
                <td class="td">{{ $a->doctor?->name }}</td><td class="td">{{ $a->service?->name }}</td>
                <td class="td"><x-badge :color="$stColors[$a->status] ?? 'slate'">{{ $a->statusLabel() }}</x-badge></td></tr>
        @empty
            <tr><td class="td text-slate-500 text-center py-8">{{ __("Bugun qabullar yo'q") }}</td></tr>
        @endforelse
    </table></div>
</div>
@endif
@endsection
@push('scripts')
@include('partials.charts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    KC.area('cRev', @json($chartLabels), @json($chartRevenue), @json(__('Tushum')));
    @php
        $names = \App\Models\Appointment::statuses();
        $cols = ['scheduled' => '#94a3b8', 'arrived' => '#f59e0b', 'in_progress' => '#2563eb', 'done' => '#22c55e', 'no_show' => '#f97316', 'canceled' => '#ef4444'];
        $keys = $statusCounts->keys();
    @endphp
    KC.donut('cStat', @json($keys->map(fn ($k) => __($names[$k] ?? $k))->values()), @json($statusCounts->values()), false, @json($keys->map(fn ($k) => $cols[$k] ?? '#64748b')->values()));
});
</script>
@endpush
