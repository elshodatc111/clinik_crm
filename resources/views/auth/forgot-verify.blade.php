@extends('layouts.auth')
@section('title', __('Yangi parol'))
@section('content')
<h1 class="text-xl font-bold">{{ __('Yangi parol belgilash') }}</h1>
@if($tg)
    <p class="text-sm text-slate-500 mb-4">+{{ $phone }} {{ __('raqamini Telegram bot orqali tasdiqlang.') }}</p>
    @include('auth.partials.tg-otp', ['flow' => 'reset'])
@else
    <p class="text-sm text-slate-500 mb-4">{{ __("Agar +:p raqamli hisob mavjud bo'lsa, unga SMS kod yuborildi.", ['p' => $phone]) }}</p>
@endif
<form method="POST" action="{{ route('password.reset') }}" class="space-y-4">
    @csrf
    <div id="tg-code-box" class="{{ $tg ? 'hidden' : '' }}">
        <x-field name="code" :label="__('Tasdiqlash kodi')" required attrs='inputmode="numeric" maxlength="6" autocomplete="one-time-code"' />
    </div>
    <x-field name="password" type="password" :label="__('Yangi parol (kamida 8 belgi)')" required attrs='autocomplete="new-password"' />
    <x-field name="password_confirmation" type="password" :label="__('Parolni takrorlang')" required attrs='autocomplete="new-password"' />
    <button class="btn btn-primary w-full">{{ __('Parolni yangilash') }}</button>
</form>
<p class="mt-4 text-sm text-center"><a class="link" href="{{ route('password.forgot') }}">{{ __('Boshqa raqam kiritish') }}</a></p>
@endsection
