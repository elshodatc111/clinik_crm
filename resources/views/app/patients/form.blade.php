@extends('layouts.app')
@section('title', $patient->exists ? __('Bemorni tahrirlash') : __('Yangi bemor'))
@section('content')
<form method="POST" action="{{ $patient->exists ? route('app.patients.update', $patient) : route('app.patients.store') }}" class="card p-6 max-w-4xl">
    @csrf
    @if($patient->exists) @method('PUT') @endif
    <div class="grid md:grid-cols-3 gap-4">
        <x-field name="last_name" :label="__('Familiya')" :value="$patient->last_name" required />
        <x-field name="first_name" :label="__('Ism')" :value="$patient->first_name" required />
        <x-field name="middle_name" :label="__('Otasining ismi')" :value="$patient->middle_name" />
        <x-field name="pinfl" :label="__('JSHSHIR (14 raqam)')" :value="$patient->pinfl" attrs='inputmode="numeric" maxlength="14"' :hint="__('Bitta bemor klinikada faqat bir marta kiritiladi')" />
        <x-field name="birth_date" type="date" :label="__('Tug\'ilgan sana')" :value="$patient->birth_date?->format('Y-m-d')" />
        <x-field name="gender" type="select" :label="__('Jinsi')" :value="$patient->gender" :options="['' => '—', 'm' => __('Erkak'), 'f' => __('Ayol')]" />
        <x-field name="phone" :label="__('Telefon')" :value="$patient->phone" placeholder="+998 90 123 45 67" />
        <x-field name="phone2" :label="__('Qo\'shimcha telefon')" :value="$patient->phone2" />
        <x-field name="blood_group" type="select" :label="__('Qon guruhi')" :value="$patient->blood_group" :options="['' => '—', 'O(I) Rh+' => 'O(I) Rh+', 'O(I) Rh-' => 'O(I) Rh-', 'A(II) Rh+' => 'A(II) Rh+', 'A(II) Rh-' => 'A(II) Rh-', 'B(III) Rh+' => 'B(III) Rh+', 'B(III) Rh-' => 'B(III) Rh-', 'AB(IV) Rh+' => 'AB(IV) Rh+', 'AB(IV) Rh-' => 'AB(IV) Rh-']" />
        <x-field name="address" :label="__('Manzil')" :value="$patient->address" span="2" />
        <x-field name="source" type="select" :label="__('Qayerdan bildi')" :value="$patient->source" :options="['' => '—', 'Instagram' => 'Instagram', 'Telegram' => 'Telegram', 'Facebook' => 'Facebook', 'Google' => 'Google', 'Tanish-bilish' => __('Tanish-bilish'), 'Reklama' => __('Reklama'), 'Boshqa' => __('Boshqa')]" />
        <x-field name="allergies" type="textarea" :label="__('Allergiya')" :value="$patient->allergies" rows="2" span="2" />
        <x-field name="chronic_diseases" type="textarea" :label="__('Surunkali kasalliklar')" :value="$patient->chronic_diseases" rows="2" span="2" />
        <x-field name="notes" type="textarea" :label="__('Izoh')" :value="$patient->notes" rows="2" span="2" />
    </div>
    <div class="mt-6 flex gap-2"><button class="btn btn-primary">{{ __('Saqlash') }}</button><a class="btn" href="{{ $patient->exists ? route('app.patients.show', $patient) : route('app.patients.index') }}">{{ __('Bekor qilish') }}</a></div>
</form>
@endsection
