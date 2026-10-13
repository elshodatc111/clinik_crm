@extends('layouts.app')
@section('title', __("Tahlil yo'llanmasi"))
@section('content')
<form method="POST" action="{{ route('app.lab.store') }}" class="card p-6 max-w-2xl space-y-4">
    @csrf
    <div><label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Bemor') }} <span class="text-red-500">*</span></label>@include('partials.patient-picker', ['patient' => $patient])</div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1" for="f_test_name">{{ __('Tahlil nomi') }} <span class="text-red-500">*</span></label>
        <input id="f_test_name" name="test_name" value="{{ old('test_name') }}" list="tests" class="input" required>
        <datalist id="tests">@foreach(['Umumiy qon tahlili', 'Umumiy siydik tahlili', 'Qon biokimyosi', 'Qand miqdori (glyukoza)', 'Lipid profili', 'Qalqonsimon bez gormonlari', 'C-reaktiv oqsil', 'Najas tahlili', 'COVID-19 PCR'] as $t)<option value="{{ $t }}">@endforeach</datalist>
        @error('test_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    @if(! auth()->user()->isScopedDoctor())<x-field name="doctor_id" type="select" :label="__('Yo\'llagan shifokor')" :options="['' => '—'] + $doctors->pluck('name', 'id')->all()" />@endif
    <div class="flex gap-2"><button class="btn btn-primary">{{ __('Yaratish') }}</button><a class="btn" href="{{ route('app.lab.index') }}">{{ __('Bekor qilish') }}</a></div>
</form>
@endsection
