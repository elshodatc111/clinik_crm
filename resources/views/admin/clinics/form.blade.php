@extends('layouts.app')
@section('title', $clinic->exists ? __('Klinikani tahrirlash') : __("Klinika qo'shish"))
@section('content')
<form method="POST" action="{{ $clinic->exists ? route('admin.clinics.update', $clinic) : route('admin.clinics.store') }}" class="card p-6 max-w-3xl">
    @csrf
    @if($clinic->exists) @method('PUT') @endif
    <div class="grid md:grid-cols-2 gap-4">
        <x-field name="name" :label="__('Klinika nomi')" :value="$clinic->name" required />
        <x-field name="owner_name" :label="__('Rahbar F.I.Sh.')" :value="$clinic->owner_name" required />
        <x-field name="phone" :label="__('Telefon')" :value="$clinic->phone" required />
        <x-field name="email" type="email" :label="__('Email')" :value="$clinic->email" />
        @if($clinic->exists)
            <x-field name="address" :label="__('Manzil')" :value="$clinic->address" span="2" />
        @else
            <x-field name="password" type="password" :label="__('Klinika admini paroli')" :hint="__('Telefon raqami login bo\'ladi')" required />
            <x-field name="status" type="select" :label="__('Boshlang\'ich holat')" :options="['trial' => __('Demo (14 kun)'), 'active' => __('Faol (to\'lov qo\'lda qabul qilingan)')]" value="trial" />
        @endif
    </div>
    <div class="mt-6 flex gap-2"><button class="btn btn-primary">{{ __('Saqlash') }}</button><a class="btn" href="{{ route('admin.clinics.index') }}">{{ __('Bekor qilish') }}</a></div>
</form>
@endsection
