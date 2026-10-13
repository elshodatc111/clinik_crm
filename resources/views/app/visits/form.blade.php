@extends('layouts.app')
@section('title', $visit->exists ? __('Tashrifni tahrirlash') : __('Yangi tashrif (ko\'rik)'))
@section('content')
@php
    $blank = ['drug' => '', 'dose' => '', 'frequency' => '', 'duration' => ''];
    $rows = old('rx', $visit->exists ? $visit->prescriptions->map(fn ($p) => ['drug' => $p->drug, 'dose' => $p->dose, 'frequency' => $p->frequency, 'duration' => $p->duration])->all() : []);
    if (! $rows) { $rows = [$blank]; }
    $rows = array_values($rows);
@endphp
<div class="card p-4 mb-4 max-w-4xl">
    <div class="font-semibold">{{ $patient->full_name }}</div>
    <div class="text-sm text-slate-500">{{ $patient->birth_date?->format('d.m.Y') }} @if($patient->age() !== null)({{ $patient->age() }})@endif {{ phone_fmt($patient->phone) }}</div>
    @if($patient->allergies)<div class="text-sm text-red-700 mt-1">⚠ {{ __('Allergiya') }}: {{ $patient->allergies }}</div>@endif
    @if($patient->chronic_diseases)<div class="text-sm text-slate-600 mt-1">{{ __('Surunkali kasalliklar') }}: {{ $patient->chronic_diseases }}</div>@endif
</div>
<form method="POST" action="{{ $visit->exists ? route('app.visits.update', $visit) : route('app.visits.store') }}" class="card p-6 max-w-4xl" x-data='{ rows: @json($rows) }'>
    @csrf
    @if($visit->exists) @method('PUT') @else
        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
        <input type="hidden" name="appointment_id" value="{{ $appointment?->id }}">
    @endif
    <div class="grid md:grid-cols-2 gap-4">
        @if(! $visit->exists && ! auth()->user()->isScopedDoctor())
            <x-field name="doctor_id" type="select" :label="__('Shifokor')" :value="$appointment?->doctor_id" :options="['' => '—'] + $doctors->pluck('name', 'id')->all()" required span="2" />
        @endif
        <x-field name="complaint" type="textarea" :label="__('Shikoyat')" :value="$visit->complaint" span="2" />
        <x-field name="anamnesis" type="textarea" :label="__('Anamnez')" :value="$visit->anamnesis" span="2" />
        <x-field name="examination" type="textarea" :label="__('Ob\'ektiv ko\'rik')" :value="$visit->examination" span="2" />
        <x-field name="diagnosis_code" :label="__('Tashxis kodi (MKB-10)')" :value="$visit->diagnosis_code" placeholder="J06.9" />
        <x-field name="diagnosis_text" type="textarea" :label="__('Tashxis')" :value="$visit->diagnosis_text" rows="2" />
        <x-field name="recommendations" type="textarea" :label="__('Tavsiyalar')" :value="$visit->recommendations" span="2" />
    </div>

    <div class="mt-6">
        <div class="font-semibold mb-2">{{ __('Retsept') }}</div>
        <template x-for="(row, i) in rows" :key="i">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-2 mb-2">
                <input class="input md:col-span-2" :name="'rx[' + i + '][drug]'" x-model="row.drug" placeholder="{{ __('Dori nomi') }}">
                <input class="input" :name="'rx[' + i + '][dose]'" x-model="row.dose" placeholder="{{ __('Dozasi') }}">
                <input class="input" :name="'rx[' + i + '][frequency]'" x-model="row.frequency" placeholder="{{ __('Necha mahal') }}">
                <div class="flex gap-1"><input class="input" :name="'rx[' + i + '][duration]'" x-model="row.duration" placeholder="{{ __('Muddati') }}"><button type="button" class="btn btn-sm" @click="rows.splice(i, 1)">✕</button></div>
            </div>
        </template>
        <button type="button" class="btn btn-sm" @click="rows.push({drug: '', dose: '', frequency: '', duration: ''})"><x-icon name="plus" class="w-4 h-4" />{{ __("Dori qo'shish") }}</button>
    </div>
    <div class="mt-6 flex gap-2"><button class="btn btn-primary">{{ __('Saqlash') }}</button><a class="btn" href="{{ route('app.patients.show', $patient) }}">{{ __('Bekor qilish') }}</a></div>
</form>
@endsection
