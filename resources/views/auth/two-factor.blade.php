@extends('layouts.auth')
@section('title', __('Ikki bosqichli tasdiqlash'))
@section('content')
<h1 class="text-xl font-bold mb-1">{{ __('Tasdiqlash kodi') }}</h1>
<p class="text-sm text-slate-500 mb-4">{{ __('Autentifikator ilovasidagi 6 xonali kodni kiriting. Telefon yo\'qolgan bo\'lsa — zaxira kodlardan birini kiriting.') }}</p>
<form method="POST" action="{{ route('login.2fa.post') }}" class="space-y-4">
    @csrf
    <x-field name="code" :label="__('Kod')" required attrs='autofocus autocomplete="one-time-code" inputmode="text"' />
    <button class="btn btn-primary w-full">{{ __('Tasdiqlash') }}</button>
</form>
<p class="mt-4 text-sm text-center"><a class="link" href="{{ route('login') }}">{{ __('Orqaga') }}</a></p>
@endsection
