@extends('layouts.app')
@section('title', __('Bosh sahifa'))
@section('content')
@php
    $me = auth()->user();
    $role = $me->effectiveRole();
    $money = $me->hasRole(...['clinic_admin', 'cashier']);
    $stColors = ['done' => 'green', 'canceled' => 'red', 'no_show' => 'red', 'in_progress' => 'blue', 'arrived' => 'yellow'];
@endphp
<div class="hero-grad rounded-2xl p-6 md:p-7 text-white flex flex-wrap items-center justify-between gap-4 shadow-lg relative overflow-hidden">
    <div class="hidden lg:block absolute right-6 -bottom-4 h-44 w-60 opacity-90 pointer-events-none"><x-illu :name="['clinic_admin' => 'director', 'receptionist' => 'reception', 'doctor' => 'doctor', 'cashier' => 'cashier', 'laborant' => 'lab'][$role] ?? 'team'" /></div>
    <div class="relative">
        <div class="text-white/70 text-sm">{{ now()->translatedFormat('d.m.Y') }}</div>
        <div class="text-2xl font-bold mt-1">{{ __('Xush kelibsiz') }}, {{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first() }}!</div>
        <div class="text-white/80 text-sm mt-1">{{ $me->roleLabel() }} · {{ __('Bugungi qabullar') }}: <b>{{ $todayCount }}</b></div>
    </div>
    <div class="flex flex-wrap gap-2 relative lg:mr-64">
        @if($me->hasRole(...['clinic_admin', 'receptionist']))<a href="{{ route('app.patients.create') }}" class="btn !bg-white !text-slate-800 border-transparent"><x-icon name="user-plus" class="w-4 h-4" />{{ __('Yangi bemor') }}</a>@endif
        @if($me->hasRole(...['clinic_admin', 'receptionist', 'doctor']))<a href="{{ route('app.appointments.create') }}" class="btn !bg-white/15 !text-white border-white/20 hover:!bg-white/25"><x-icon name="calendar" class="w-4 h-4" />{{ __('Qabulga yozish') }}</a>@endif
        @if($me->hasRole(...['clinic_admin', 'receptionist', 'cashier']))<a href="{{ route('app.invoices.create') }}" class="btn !bg-white/15 !text-white border-white/20 hover:!bg-white/25"><x-icon name="receipt" class="w-4 h-4" />{{ __('Hisob-faktura') }}</a>@endif
    </div>
</div>

@if(count($issues))
<div class="card mt-5 overflow-hidden" x-data="{ all: false }">
    <div class="section-title justify-between">
        <span class="flex items-center gap-2"><x-icon name="info" class="w-4 h-4 text-brand" />{{ __('Tavsiyalar va kamchiliklar') }} <span class="badge bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20">{{ count($issues) }}</span></span>
        @if(count($issues) > 3)<button type="button" @click="all = !all" class="text-xs link" x-text="all ? @js(__('Kamroq')) : @js(__('Hammasi'))"></button>@endif
    </div>
    <div class="divide-y divide-slate-100">
        @foreach($issues as $k => $i)
            <div class="px-5 py-3 flex items-start justify-between gap-3" @if($k >= 3) x-show="all" x-cloak @endif>
                <div class="flex items-start gap-3 min-w-0">
                    <span class="mt-1.5 h-2 w-2 rounded-full shrink-0 {{ ['danger' => 'bg-red-500', 'warning' => 'bg-amber-500'][$i['level']] ?? 'bg-blue-400' }}"></span>
                    <div class="text-sm"><div class="font-medium text-slate-900">{{ $i['title'] }}</div><div class="text-slate-500">{{ $i['body'] }}</div></div>
                </div>
                @if($i['route'])<a href="{{ route($i['route']) }}" class="btn btn-sm shrink-0">{{ __('Tuzatish') }}</a>@endif
            </div>
        @endforeach
    </div>
</div>
@endif

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

@if($me->hasRole(...['clinic_admin', 'receptionist', 'doctor']))
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
