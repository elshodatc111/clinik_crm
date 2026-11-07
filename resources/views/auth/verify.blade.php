@extends('layouts.auth')
@section('title', __('Tasdiqlash'))
@section('content')
<h1 class="text-xl font-bold">{{ __('Telefonni tasdiqlang') }}</h1>
@if($tg)
    <p class="text-sm text-slate-500 mb-4">+{{ $phone }} {{ __('raqamini Telegram bot orqali tasdiqlang.') }}</p>
    @include('auth.partials.tg-otp', ['flow' => 'register'])
@else
    <p class="text-sm text-slate-500 mb-4">+{{ $phone }} {{ __('raqamiga 6 xonali kod yuborildi.') }}</p>
    @if(session('dev_code'))
        <div class="mb-4 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
            {{ __('TEST REJIMI (SMS sozlanmagan): tasdiqlash kodi') }} <b>{{ session('dev_code') }}</b>
        </div>
    @endif
@endif
<form method="POST" action="{{ route('register.verify.post') }}" class="space-y-4">
    @csrf
    <div id="tg-code-box" class="{{ $tg ? 'hidden' : '' }}">
        <x-field name="code" :label="$tg ? __('Telegramdan kelgan kod') : __('SMS kod')" required attrs='inputmode="numeric" maxlength="6" autocomplete="one-time-code"' />
    </div>
    <button class="btn btn-primary w-full">{{ __('Tasdiqlash va boshlash') }}</button>
</form>
@unless($tg)
<form method="POST" action="{{ route('register.resend') }}" class="mt-3 text-center">@csrf<button class="link text-sm">{{ __('Kodni qayta yuborish') }}</button></form>
@endunless
@endsection
