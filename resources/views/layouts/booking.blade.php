<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    @include('partials.head')
    <title>@yield('title') — {{ $clinic->name }}</title>
    <meta name="robots" content="noindex">
</head>
<body class="min-h-screen text-slate-800 antialiased">
<header class="bg-white/80 backdrop-blur border-b border-slate-200/70 sticky top-0 z-20">
    <div class="max-w-5xl mx-auto px-4 h-16 flex items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
            @if($clinic->logoUrl())<img src="{{ $clinic->logoUrl() }}" class="h-10 w-10 rounded-lg object-contain" alt="">
            @else<span class="h-10 w-10 rounded-xl bg-brand text-white grid place-items-center"><x-icon name="heart-pulse" class="w-5 h-5" /></span>@endif
            <div class="min-w-0"><div class="font-semibold text-slate-900 truncate">{{ $clinic->name }}</div><div class="text-xs text-slate-500 truncate">{{ $clinic->address }}</div></div>
        </div>
        <div class="flex items-center gap-2 text-xs font-semibold">
            <div class="flex rounded-lg bg-slate-100 p-0.5">
                <a href="{{ route('lang', 'uz') }}" class="px-2.5 py-1 rounded-md {{ app()->getLocale() === 'uz' ? 'bg-white shadow text-brand' : 'text-slate-500' }}">UZ</a>
                <a href="{{ route('lang', 'ru') }}" class="px-2.5 py-1 rounded-md {{ app()->getLocale() === 'ru' ? 'bg-white shadow text-brand' : 'text-slate-500' }}">RU</a>
            </div>
            <button type="button" onclick="toggleTheme()" class="btn btn-sm !px-2" aria-label="Theme"><x-icon name="moon" class="w-4 h-4 dark:hidden" /><x-icon name="sun" class="w-4 h-4 hidden dark:block" /></button>
        </div>
    </div>
</header>
<main class="max-w-5xl mx-auto px-4 py-8">@yield('content')</main>
<footer class="max-w-5xl mx-auto px-4 pb-10 text-xs text-slate-500 flex flex-wrap gap-x-4 gap-y-1">
    @if($clinic->phone)<span class="inline-flex items-center gap-1"><x-icon name="phone" class="w-3.5 h-3.5" />{{ phone_fmt($clinic->phone) }}</span>@endif
    @if($clinic->setting('working_hours'))<span class="inline-flex items-center gap-1"><x-icon name="clock" class="w-3.5 h-3.5" />{{ $clinic->setting('working_hours') }}</span>@endif
    @if($clinic->setting('map_link'))<a class="link" href="{{ $clinic->setting('map_link') }}" target="_blank" rel="noopener">{{ __('Xarita') }}</a>@endif
</footer>
@stack('scripts')
</body>
</html>
