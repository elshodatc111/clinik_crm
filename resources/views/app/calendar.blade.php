@extends('layouts.app')
@section('title', __('Qabul kalendari'))
@section('content')
@php
    $u = auth()->user();
    $ppm = 1.1;                                  // 1 daqiqa = 1.1 px
    $height = ($endHour - $startHour) * 60 * $ppm;
    $step = $view === 'week' ? 7 : 1;
    $prev = $date->copy()->subDays($step)->toDateString();
    $next = $date->copy()->addDays($step)->toDateString();
    $q = fn (array $x) => route('app.calendar', array_merge(['view' => $view, 'date' => $date->toDateString(), 'doctor_id' => $doctorId], $x));
    $colors = [
        'scheduled' => 'bg-blue-50 border-blue-500 text-blue-900', 'arrived' => 'bg-amber-50 border-amber-500 text-amber-900',
        'in_progress' => 'bg-violet-50 border-violet-500 text-violet-900', 'done' => 'bg-emerald-50 border-emerald-500 text-emerald-900',
        'no_show' => 'bg-slate-100 border-slate-400 text-slate-600',
    ];
@endphp
<div class="card p-3 flex flex-wrap items-center justify-between gap-3 mb-4">
    <div class="flex items-center gap-2">
        <a class="btn btn-sm" href="{{ $q(['date' => $prev]) }}">←</a>
        <a class="btn btn-sm" href="{{ $q(['date' => today()->toDateString()]) }}">{{ __('Bugun') }}</a>
        <a class="btn btn-sm" href="{{ $q(['date' => $next]) }}">→</a>
        <form method="GET" class="flex items-center gap-2">
            <input type="hidden" name="view" value="{{ $view }}"><input type="hidden" name="doctor_id" value="{{ $doctorId }}">
            <input type="date" name="date" value="{{ $date->toDateString() }}" class="input !py-1.5" onchange="this.form.submit()">
        </form>
        <span class="text-sm font-semibold text-slate-700 hidden md:inline">
            @if($view === 'week') {{ $columns->first()['date']->format('d.m') }} – {{ $columns->last()['date']->format('d.m.Y') }} @else {{ $date->translatedFormat('d F Y, l') }} @endif
        </span>
    </div>
    <div class="flex items-center gap-2">
        @if($view === 'week' && ! $u->isScopedDoctor())
            <form method="GET"><input type="hidden" name="view" value="week"><input type="hidden" name="date" value="{{ $date->toDateString() }}">
                <select name="doctor_id" class="input !py-1.5" onchange="this.form.submit()">@foreach($doctors as $d)<option value="{{ $d->id }}" @selected((string) $doctorId === (string) $d->id)>{{ $d->name }}</option>@endforeach</select></form>
        @endif
        <div class="flex rounded-lg bg-slate-100 p-0.5 text-xs font-semibold">
            <a href="{{ $q(['view' => 'day']) }}" class="px-3 py-1.5 rounded-md {{ $view === 'day' ? 'bg-white shadow text-brand' : 'text-slate-500' }}">{{ __('Kun') }}</a>
            <a href="{{ $q(['view' => 'week']) }}" class="px-3 py-1.5 rounded-md {{ $view === 'week' ? 'bg-white shadow text-brand' : 'text-slate-500' }}">{{ __('Hafta') }}</a>
        </div>
        <a href="{{ route('app.appointments.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="w-4 h-4" />{{ __('Qabulga yozish') }}</a>
    </div>
</div>

@if($columns->isEmpty())
    <div class="card p-10 text-center text-slate-500">{{ __("Shifokorlar yo'q. Avval Xodimlar bo'limida shifokor qo'shing.") }}</div>
@else
<div class="card overflow-auto">
    <div class="min-w-[720px]">
        {{-- sarlavha --}}
        <div class="flex sticky top-0 z-10 bg-white border-b border-slate-200">
            <div class="w-14 shrink-0"></div>
            @foreach($columns as $c)
                <div class="flex-1 min-w-[150px] px-2 py-2.5 text-center border-l border-slate-100 {{ $c['today'] ? 'bg-brand-soft' : '' }}">
                    <div class="text-sm font-semibold text-slate-800 truncate">{{ $c['title'] }}</div>
                    @if(! empty($c['sub']))<div class="text-[11px] text-slate-500">{{ $c['sub'] }}</div>@endif
                </div>
            @endforeach
        </div>
        <div class="flex">
            {{-- soatlar --}}
            <div class="w-14 shrink-0 relative" style="height: {{ $height }}px">
                @for($h = $startHour; $h < $endHour; $h++)
                    <div class="absolute right-2 -translate-y-2 text-[11px] text-slate-400" style="top: {{ ($h - $startHour) * 60 * $ppm }}px">{{ sprintf('%02d:00', $h) }}</div>
                @endfor
            </div>
            @foreach($columns as $c)
                <div class="flex-1 min-w-[150px] relative border-l border-slate-100 {{ $c['today'] ? 'bg-brand-soft' : '' }}" style="height: {{ $height }}px">
                    {{-- bo'sh katak: bosilsa shu vaqtga qabul yozish --}}
                    @for($m = 0; $m < ($endHour - $startHour) * 60; $m += 30)
                        @php
                            $slot = $c['date']->copy()->setTime($startHour, 0)->addMinutes($m);
                        @endphp
                        <a href="{{ route('app.appointments.create', ['starts_at' => $slot->format('Y-m-d\TH:i'), 'doctor_id' => $c['doctor_id']]) }}"
                           class="absolute inset-x-0 border-t {{ $m % 60 === 0 ? 'border-slate-200' : 'border-dashed border-slate-100' }} slot"
                           style="top: {{ $m * $ppm }}px; height: {{ 30 * $ppm }}px" title="{{ $slot->format('H:i') }} — {{ __('Qabulga yozish') }}"></a>
                    @endfor
                    @foreach($byCol[$c['key']] ?? [] as $a)
                        @php
                            $top = max(0, ($a->starts_at->hour - $startHour) * 60 + $a->starts_at->minute) * $ppm;
                            $eh = max(22, $a->starts_at->diffInMinutes($a->ends_at) * $ppm - 2);
                        @endphp
                        <a href="{{ route('app.appointments.edit', $a) }}" class="absolute inset-x-1 rounded-lg border-l-4 px-2 py-1 text-xs overflow-hidden shadow-sm hover:shadow-md hover:z-10 transition {{ $colors[$a->status] ?? $colors['scheduled'] }}"
                           style="top: {{ $top + 1 }}px; height: {{ $eh }}px" title="{{ $a->patient?->full_name }} · {{ $a->statusLabel() }}">
                            <div class="font-semibold truncate">{{ $a->starts_at->format('H:i') }} {{ $a->patient?->full_name }}</div>
                            @if($eh > 38)<div class="truncate opacity-80">{{ $a->service?->name }}</div>@endif
                        </a>
                    @endforeach
                    {{-- hozirgi vaqt chizig'i --}}
                    @if($c['date']->isToday() && now()->hour >= $startHour && now()->hour < $endHour)
                        <div class="absolute inset-x-0 z-10 border-t-2 border-red-500 pointer-events-none" style="top: {{ ((now()->hour - $startHour) * 60 + now()->minute) * $ppm }}px"><span class="absolute -left-1 -top-[5px] h-2 w-2 rounded-full bg-red-500"></span></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
<div class="flex flex-wrap gap-3 mt-3 text-xs text-slate-500">
    @foreach(['scheduled' => 'Kutilmoqda', 'arrived' => 'Keldi', 'in_progress' => 'Qabulda', 'done' => 'Tugadi', 'no_show' => 'Kelmadi'] as $k => $l)
        <span class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded border-l-4 {{ $colors[$k] }}"></span>{{ __($l) }}</span>
    @endforeach
    <span class="ml-auto">{{ __("Bo'sh katakni bosing — shu vaqtga qabul yoziladi") }}</span>
</div>
@endif
@endsection
