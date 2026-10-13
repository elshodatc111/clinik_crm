<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    @include('partials.head')
    <title>@yield('title') — {{ $brand['name'] }}</title>
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-slate-100 min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-md">
    <a href="{{ route('landing') }}" class="flex items-center justify-center gap-2 mb-6 font-bold text-xl">
        @if($brand['logo'])<img src="{{ $brand['logo'] }}" class="h-10 w-10 object-contain" alt="">@else<span class="h-11 w-11 rounded-xl bg-brand text-white grid place-items-center shadow-lg"><x-icon name="heart-pulse" class="w-6 h-6" /></span>@endif
        {{ $brand['name'] }}
    </a>
    <div class="card p-7 shadow-xl shadow-blue-900/5">
        <x-flash />
        @yield('content')
    </div>
    <div class="mt-4 text-center text-sm text-slate-500">
        <a href="{{ route('lang', 'uz') }}" class="{{ app()->getLocale() === 'uz' ? 'font-bold text-brand' : '' }}">UZ</a> ·
        <a href="{{ route('lang', 'ru') }}" class="{{ app()->getLocale() === 'ru' ? 'font-bold text-brand' : '' }}">RU</a>
    </div>
</div>
</body>
</html>
