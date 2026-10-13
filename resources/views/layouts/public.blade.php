<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    @include('partials.head')
    <title>@yield('title', $brand['name'])</title>
    <meta name="description" content="@yield('description', __('Xususiy klinikalar uchun to\'liq avtomatlashtirilgan CRM tizimi'))">
</head>
<body class="bg-white text-slate-800 antialiased">
<header class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-slate-200">
    <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
        <a href="{{ route('landing') }}" class="flex items-center gap-2 font-bold text-lg">
            @if($brand['logo'])<img src="{{ $brand['logo'] }}" class="h-8 w-8 object-contain" alt="">@else<span class="h-9 w-9 rounded-xl bg-brand text-white grid place-items-center"><x-icon name="heart-pulse" class="w-5 h-5" /></span>@endif
            {{ $brand['name'] }}
        </a>
        <div class="flex items-center gap-3 text-sm">
            <a href="{{ route('lang', 'uz') }}" class="{{ app()->getLocale() === 'uz' ? 'font-bold text-brand' : 'text-slate-500' }}">UZ</a>
            <a href="{{ route('lang', 'ru') }}" class="{{ app()->getLocale() === 'ru' ? 'font-bold text-brand' : 'text-slate-500' }}">RU</a>
            <button type="button" onclick="toggleTheme()" class="btn btn-sm !px-2" aria-label="Theme"><x-icon name="moon" class="w-4 h-4 dark:hidden" /><x-icon name="sun" class="w-4 h-4 hidden dark:block" /></button>
            @auth
                <a class="btn btn-primary" href="{{ route('dashboard') }}">{{ __('Panelga kirish') }}</a>
            @else
                <a class="btn" href="{{ route('login') }}">{{ __('Kirish') }}</a>
                <a class="btn btn-primary" href="{{ route('register') }}">{{ __("Ro'yxatdan o'tish") }}</a>
            @endauth
        </div>
    </div>
</header>
@yield('content')
<footer class="border-t border-slate-200 mt-16 py-8 text-sm text-slate-500">
    <div class="max-w-6xl mx-auto px-4 flex flex-wrap items-center justify-between gap-3">
        <span>© {{ date('Y') }} {{ $brand['name'] }}</span>
        <span class="flex gap-4">
            <a href="{{ route('offer') }}" class="hover:text-brand">{{ __('Ommaviy oferta') }}</a>
            @if($brand['phone'])<a href="tel:{{ $brand['phone'] }}" class="hover:text-brand">{{ $brand['phone'] }}</a>@endif
            @if($brand['email'])<a href="mailto:{{ $brand['email'] }}" class="hover:text-brand">{{ $brand['email'] }}</a>@endif
        </span>
    </div>
</footer>
</body>
</html>
