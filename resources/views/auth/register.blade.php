@extends('layouts.auth')
@section('title', __("Ro'yxatdan o'tish"))
@section('content')
<h1 class="text-xl font-bold">{{ __("Ro'yxatdan o'tish") }}</h1>
<p class="text-sm text-slate-500 mb-4">{{ __(':n kunlik bepul demo. Karta talab qilinmaydi.', ['n' => $demoDays]) }}</p>
<form method="POST" action="{{ route('register.post') }}" class="space-y-4" x-data="{ accepted: false }">
    @csrf
    <x-field name="clinic_name" :label="__('Klinika nomi')" :value="old('clinic_name')" required />
    <x-field name="owner_name" :label="__('Rahbar F.I.Sh.')" :value="old('owner_name')" required />
    <x-field name="phone" type="tel" :label="__('Telefon raqami')" :value="old('phone')" placeholder="+998 90 123 45 67" :hint="__('SMS orqali tasdiqlash kodi yuboriladi')" required />
    <x-field name="email" type="email" :label="__('Email (ixtiyoriy)')" :value="old('email')" />
    <x-field name="password" type="password" :label="__('Parol (kamida 8 belgi)')" required />
    <x-field name="password_confirmation" type="password" :label="__('Parolni takrorlang')" required />
    <label class="flex items-start gap-2 text-sm">
        <input type="checkbox" name="accept_offer" value="1" x-model="accepted" class="mt-1 rounded border-slate-300">
        <span>{{ __('Men') }} <a href="{{ route('offer') }}" target="_blank" class="link">{{ __('ommaviy oferta')}}</a> {{ __('shartlari bilan tanishdim va roziman') }}</span>
    </label>
    @error('accept_offer')<p class="text-xs text-red-600">{{ __('Davom etish uchun ofertaga rozilik bering.') }}</p>@enderror
    <button class="btn btn-primary w-full" :disabled="!accepted" :class="!accepted && 'opacity-50 cursor-not-allowed'">{{ __('Kodni yuborish') }}</button>
</form>
<p class="mt-4 text-sm text-center text-slate-600">{{ __('Allaqachon ro\'yxatdan o\'tganmisiz?') }} <a class="link" href="{{ route('login') }}">{{ __('Kirish') }}</a></p>
@endsection
