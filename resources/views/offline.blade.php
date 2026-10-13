<!doctype html>
<html lang="{{ app()->getLocale() }}"><head>@include('partials.head')<title>{{ __('Internet yo\'q') }}</title></head>
<body class="min-h-screen grid place-items-center text-center p-6 text-slate-700">
<div><div class="w-48 mx-auto"><x-illu name="empty" /></div>
<h1 class="text-2xl font-bold mt-4">{{ __('Internetga ulanish yo\'q') }}</h1>
<p class="text-slate-500 mt-2">{{ __('Ulanish tiklanganda sahifa o\'zi ochiladi.') }}</p>
<button onclick="location.reload()" class="btn btn-primary mt-5">{{ __('Qayta urinish') }}</button></div>
<script>addEventListener('online',()=>location.reload())</script>
</body></html>
