@extends('layouts.auth')
@section('title', __('Parolni tiklash'))
@section('content')
<h1 class="text-xl font-bold">{{ __('Parolni tiklash') }}</h1>
<p class="text-sm text-slate-500 mb-4">{{ __("Hisobga ulangan telefon raqamingizni kiriting. Tasdiqlash kodi Telegram bot orqali yuboriladi.") }}</p>
<form method="POST" action="{{ route('password.forgot.post') }}" class="space-y-4">
    @csrf
    <x-field name="phone" type="tel" :label="__('Telefon raqami')" :value="old('phone')" placeholder="+998 90 123 45 67" required attrs='autofocus autocomplete="tel"' />
    <button class="btn btn-primary w-full">{{ __('Davom etish') }}</button>
</form>
<p class="mt-4 text-sm text-center"><a class="link" href="{{ route('login') }}">{{ __('Kirishga qaytish') }}</a></p>
@endsection
