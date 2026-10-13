@extends('layouts.app')
@section('title', __('Qabullar'))
@section('content')
@php($u = auth()->user())
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div class="flex items-center gap-2">
        <a class="btn btn-sm" href="{{ route('app.appointments.index', ['date' => $date->copy()->subDay()->toDateString(), 'doctor_id' => $doctorId]) }}">←</a>
        <form method="GET" class="flex gap-2 items-center">
            <input type="date" name="date" value="{{ $date->toDateString() }}" class="input" onchange="this.form.submit()">
            @if($u->role !== 'doctor')
                <select name="doctor_id" class="input" onchange="this.form.submit()"><option value="">{{ __('Barcha shifokorlar') }}</option>@foreach($doctors as $d)<option value="{{ $d->id }}" @selected((string) $doctorId === (string) $d->id)>{{ $d->name }}</option>@endforeach</select>
            @endif
        </form>
        <a class="btn btn-sm" href="{{ route('app.appointments.index', ['date' => $date->copy()->addDay()->toDateString(), 'doctor_id' => $doctorId]) }}">→</a>
    </div>
    <a href="{{ route('app.appointments.create') }}" class="btn btn-primary"><x-icon name="plus" class="w-4 h-4" />{{ __('Qabulga yozish') }}</a>
</div>
<div class="card overflow-x-auto">
    <table class="w-full">
        <thead><tr><th class="th">{{ __('Vaqt') }}</th><th class="th">{{ __('Bemor') }}</th><th class="th">{{ __('Shifokor') }}</th><th class="th">{{ __('Xizmat') }}</th><th class="th">{{ __('Holat') }}</th><th class="th"></th></tr></thead>
        @forelse($items as $a)
            <tr>
                <td class="td whitespace-nowrap font-medium">{{ $a->starts_at->format('H:i') }}–{{ $a->ends_at->format('H:i') }}</td>
                <td class="td"><a class="link" href="{{ route('app.patients.show', $a->patient_id) }}">{{ $a->patient?->full_name }}</a><div class="text-xs text-slate-500">{{ phone_fmt($a->patient?->phone) }}</div></td>
                <td class="td">{{ $a->doctor?->name }}</td><td class="td">{{ $a->service?->name }}<div class="text-xs text-slate-500">{{ $a->comment }}</div></td>
                <td class="td">
                    <form method="POST" action="{{ route('app.appointments.status', $a) }}">@csrf
                        <select name="status" class="input py-1 text-xs" onchange="this.form.submit()">@foreach(\App\Models\Appointment::statuses() as $k => $l)<option value="{{ $k }}" @selected($a->status === $k)>{{ __($l) }}</option>@endforeach</select>
                    </form>
                </td>
                <td class="td text-right whitespace-nowrap">
                    @if($u->hasRole('clinic_admin', 'doctor') && ! in_array($a->status, ['done', 'canceled', 'no_show']))<a class="btn btn-sm btn-primary" href="{{ route('app.visits.create', ['appointment' => $a->id]) }}">{{ __('Qabulni boshlash') }}</a>@endif
                    <a class="btn btn-sm" href="{{ route('app.appointments.edit', $a) }}">{{ __('Tahrirlash') }}</a>
                </td>
            </tr>
        @empty<tr><td class="td text-slate-500" colspan="6">{{ __("Bu kunda qabullar yo'q") }}</td></tr>@endforelse
    </table>
</div>
@endsection
