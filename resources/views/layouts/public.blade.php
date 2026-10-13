<!doctype html>
<html lang="{{ app()->getLocale() }}" style="scroll-behavior:smooth">
<head>
    @include('partials.head')
    <title>@yield('title', $brand['name'])</title>
    <meta name="description" content="@yield('description', __('Xususiy klinikalar uchun to\'liq avtomatlashtirilgan CRM tizimi'))">
</head>
<body class="bg-white text-slate-800 antialiased">
@php
    $ct = $brand['contact'] ?? [];
    $tg = ! empty($ct['telegram']) ? (str_starts_with($ct['telegram'], 'http') ? $ct['telegram'] : 'https://t.me/'.ltrim($ct['telegram'], '@')) : null;
    $ig = ! empty($ct['instagram']) ? (str_starts_with($ct['instagram'], 'http') ? $ct['instagram'] : 'https://instagram.com/'.ltrim($ct['instagram'], '@')) : null;
@endphp
<header class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-slate-200" x-data="{ m: false }">
    <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between gap-4">
        <a href="{{ route('landing') }}" class="flex items-center gap-2 font-bold text-lg">
            @if($brand['logo'])<img src="{{ $brand['logo'] }}" class="h-8 w-8 object-contain" alt="">@else<span class="h-9 w-9 rounded-xl bg-brand text-white grid place-items-center"><x-icon name="heart-pulse" class="w-5 h-5" /></span>@endif
            {{ $brand['name'] }}
        </a>
        <nav class="hidden lg:flex items-center gap-6 text-sm font-medium text-slate-600">
            <a href="{{ route('landing') }}#features" class="hover:text-brand">{{ __('Imkoniyatlar') }}</a>
            <a href="{{ route('landing') }}#how" class="hover:text-brand">{{ __('Qanday boshlanadi') }}</a>
            <a href="{{ route('landing') }}#pricing" class="hover:text-brand">{{ __('Tariflar') }}</a>
            <a href="{{ route('landing') }}#faq" class="hover:text-brand">{{ __('Savollar') }}</a>
            <a href="{{ route('landing') }}#contact" class="hover:text-brand">{{ __("Bog'lanish") }}</a>
        </nav>
        <div class="flex items-center gap-3 text-sm">
            <a href="{{ route('lang', 'uz') }}" class="{{ app()->getLocale() === 'uz' ? 'font-bold text-brand' : 'text-slate-500' }}">UZ</a>
            <a href="{{ route('lang', 'ru') }}" class="{{ app()->getLocale() === 'ru' ? 'font-bold text-brand' : 'text-slate-500' }}">RU</a>
            <button type="button" onclick="toggleTheme()" class="btn btn-sm !px-2" aria-label="Theme"><x-icon name="moon" class="w-4 h-4 dark:hidden" /><x-icon name="sun" class="w-4 h-4 hidden dark:block" /></button>
            @auth
                <a class="btn btn-primary" href="{{ route('dashboard') }}">{{ __('Panelga kirish') }}</a>
            @else
                <a class="btn hidden sm:inline-flex" href="{{ route('login') }}">{{ __('Kirish') }}</a>
                <a class="btn btn-primary" href="{{ route('register') }}">{{ __("Ro'yxatdan o'tish") }}</a>
            @endauth
        </div>
    </div>
</header>
@yield('content')
<footer class="bg-slate-900 text-slate-400">
    <div class="max-w-6xl mx-auto px-4 py-12 grid md:grid-cols-3 gap-8 text-sm">
        <div>
            <div class="flex items-center gap-2 text-white font-bold text-lg">
                @if($brand['logo'])<img src="{{ $brand['logo'] }}" class="h-8 w-8 object-contain rounded bg-white p-0.5" alt="">@else<span class="h-9 w-9 rounded-xl bg-brand text-white grid place-items-center"><x-icon name="heart-pulse" class="w-5 h-5" /></span>@endif
                {{ $brand['name'] }}
            </div>
            <p class="mt-3 max-w-xs">{{ __("Xususiy klinikalar uchun zamonaviy boshqaruv tizimi: qabul, tibbiy karta, kassa va onlayn yozilish.") }}</p>
        </div>
        <div>
            <div class="text-white font-semibold mb-3">{{ __('Havolalar') }}</div>
            <div class="space-y-2">
                <a class="block hover:text-white" href="{{ route('landing') }}#features">{{ __('Imkoniyatlar') }}</a>
                <a class="block hover:text-white" href="{{ route('landing') }}#pricing">{{ __('Tariflar') }}</a>
                <a class="block hover:text-white" href="{{ route('offer') }}">{{ __('Ommaviy oferta') }}</a>
                <a class="block hover:text-white" href="{{ route('privacy') }}">{{ __('Maxfiylik siyosati') }}</a>
                <a class="block hover:text-white" href="{{ route('login') }}">{{ __('Kirish') }}</a>
            </div>
        </div>
        <div>
            <div class="text-white font-semibold mb-3">{{ __("Bog'lanish") }}</div>
            <div class="space-y-2">
                @if(! empty($ct['phone']))<a class="flex items-center gap-2 hover:text-white" href="tel:{{ preg_replace('/[^+\d]/', '', $ct['phone']) }}"><x-icon name="phone" class="w-4 h-4" />{{ $ct['phone'] }}</a>@endif
                @if(! empty($ct['email']))<a class="flex items-center gap-2 hover:text-white" href="mailto:{{ $ct['email'] }}"><x-icon name="mail" class="w-4 h-4" />{{ $ct['email'] }}</a>@endif
                @if(! empty($ct['address']))<div class="flex items-start gap-2"><x-icon name="map-pin" class="w-4 h-4 mt-0.5" />{{ $ct['address'] }}</div>@endif
                @if($tg)<a class="flex items-center gap-2 hover:text-white" href="{{ $tg }}" target="_blank" rel="noopener"><x-icon name="send" class="w-4 h-4" />Telegram</a>@endif
                @if($ig)<a class="flex items-center gap-2 hover:text-white" href="{{ $ig }}" target="_blank" rel="noopener"><x-icon name="globe" class="w-4 h-4" />Instagram</a>@endif
            </div>
        </div>
    </div>
    <div class="border-t border-white/10 py-4 text-center text-xs">© {{ date('Y') }} {{ $brand['name'] }}</div>
</footer>
</body>
</html>
