@extends('layouts.auth')
@section('title', __('Kirish'))
@section('content')
<h1 class="text-xl font-bold mb-4">{{ __('Tizimga kirish') }}</h1>
<form method="POST" action="{{ route('login.post') }}" class="space-y-4">
    @csrf
    <x-field name="login" :label="__('Telefon yoki email')" :value="old('login')" required attrs='autofocus autocomplete="username"' />
    <x-field name="password" type="password" :label="__('Parol')" required attrs='autocomplete="current-password"' />
    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="remember" value="1" class="rounded border-slate-300"> {{ __('Eslab qolish') }}</label>
    <button class="btn btn-primary w-full">{{ __('Kirish') }}</button>
</form>
<p class="mt-4 text-sm text-center text-slate-600">{{ __('Klinikangiz hali tizimda yo\'qmi?') }} <a class="link" href="{{ route('register') }}">{{ __("Ro'yxatdan o'ting") }}</a></p>
@endsection
