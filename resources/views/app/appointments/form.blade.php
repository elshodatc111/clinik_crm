@extends('layouts.app')
@section('title', $appointment->exists ? __('Qabulni tahrirlash') : __('Qabulga yozish'))
@section('content')
<form method="POST" action="{{ $appointment->exists ? route('app.appointments.update', $appointment) : route('app.appointments.store') }}" class="card p-6 max-w-3xl">
    @csrf
    @if($appointment->exists) @method('PUT') @endif
    <div class="grid md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Bemor') }} <span class="text-red-500">*</span></label>
            @include('partials.patient-picker', ['patient' => $patient])
            <a href="{{ route('app.patients.create') }}" class="text-xs link">{{ __("Bemor ro'yxatda yo'qmi? Yangi bemor qo'shish") }}</a>
        </div>
        <x-field name="doctor_id" type="select" :label="__('Shifokor')" :value="$appointment->doctor_id" :options="['' => '—'] + $doctors->pluck('name', 'id')->all()" required />
        <x-field name="service_id" type="select" :label="__('Xizmat')" :value="$appointment->service_id" :options="['' => '—'] + $services->mapWithKeys(fn ($s) => [$s->id => $s->name.' ('.number_format($s->price, 0, '.', ' ').')'])->all()" />
        <x-field name="starts_at" type="datetime-local" :label="__('Boshlanish vaqti')" :value="$appointment->starts_at?->format('Y-m-d\TH:i')" required />
        <x-field name="duration_min" type="number" :label="__('Davomiyligi (daqiqa)')" :value="$appointment->exists ? $appointment->starts_at->diffInMinutes($appointment->ends_at) : null" :hint="__('Bo\'sh qoldirilsa xizmat davomiyligi (yoki 30 daqiqa) olinadi')" />
        <x-field name="comment" type="textarea" :label="__('Izoh')" :value="$appointment->comment" rows="2" span="2" />
        @unless($appointment->exists)<x-field name="send_sms" type="checkbox" :label="__('Bemorga SMS yuborish')" span="2" />@endunless
    </div>
    <div class="mt-6 flex gap-2"><button class="btn btn-primary">{{ __('Saqlash') }}</button><a class="btn" href="{{ route('app.appointments.index') }}">{{ __('Bekor qilish') }}</a></div>
</form>
@endsection
