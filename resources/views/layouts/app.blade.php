<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    @include('partials.head')
    <title>@yield('title', $brand['name']) — {{ $brand['name'] }}</title>
</head>
<body class="text-slate-800 antialiased">
@php
    $u = auth()->user();
    $role = $u->effectiveRole();
    $isAdminArea = request()->routeIs('admin.*') || ($u->isSuper() && ! session('impersonate_clinic_id') && request()->routeIs('account.*', 'notifications.*'));
    $groups = $isAdminArea ? \App\Support\Menu::admin() : \App\Support\Menu::clinic();
@endphp
<div x-data="{ open: false }" class="min-h-screen flex">
    <aside class="fixed inset-y-0 left-0 z-40 w-64 bg-slate-900 text-slate-300 flex flex-col transform transition-transform md:translate-x-0 md:sticky md:top-0 md:h-screen md:shrink-0"
           :class="open ? 'translate-x-0' : '-translate-x-full md:translate-x-0'">
        <div class="h-16 flex items-center gap-3 px-5 border-b border-white/10">
            @if(! $isAdminArea && isset($clinic) && $clinic->logoUrl())
                <img src="{{ $clinic->logoUrl() }}" class="h-9 w-9 rounded-lg bg-white object-contain p-0.5" alt="">
                <span class="font-semibold text-white truncate">{{ $clinic->name }}</span>
            @elseif($brand['logo'])
                <img src="{{ $brand['logo'] }}" class="h-9 w-9 rounded-lg bg-white object-contain p-0.5" alt="">
                <span class="font-semibold text-white truncate">{{ $brand['name'] }}</span>
            @else
                <div class="h-9 w-9 rounded-xl bg-brand text-white grid place-items-center"><x-icon name="heart-pulse" class="w-5 h-5" /></div>
                <span class="font-semibold text-white truncate">{{ (! $isAdminArea && isset($clinic)) ? $clinic->name : $brand['name'] }}</span>
            @endif
        </div>
        <nav class="p-3 space-y-1 overflow-y-auto flex-1">
            @foreach($groups as $g)
                @php
                    $vis = [];
                    foreach ($g['items'] as $it) {
                        if (in_array('*', $it[2]) || $u->hasRole(...$it[2]) || ($u->isSuper() && $isAdminArea)) { $vis[] = $it; }
                    }
                    $isActive = function ($route) {
                        if ($route === 'app.sms.index') { return request()->routeIs('app.sms.index'); }
                        return request()->routeIs($route, preg_replace('/\.(index|edit)$/', '.*', $route)) || ($route === 'app.billing' && request()->routeIs('app.billing*'));
                    };
                    $hasActive = collect($vis)->contains(fn ($it) => $isActive($it[0]));
                @endphp
                @if(count($vis))
                <div x-data="{ o: {{ ($g['open'] || $hasActive) ? 'true' : 'false' }} }" class="{{ $g['label'] ? 'pt-2' : '' }}">
                    @if($g['label'])
                        <button type="button" @click="o = !o" class="w-full flex items-center justify-between px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wider text-slate-500 hover:text-slate-300">
                            <span>{{ __($g['label']) }}</span><span class="transition" :class="o ? '' : '-rotate-90'"><x-icon name="chevron-down" class="w-3.5 h-3.5" /></span>
                        </button>
                    @endif
                    <div x-show="o" x-cloak class="space-y-0.5">
                        @foreach($vis as [$route, $label, $roles, $icon])
                            @php
                                $active = $isActive($route);
                            @endphp
                            <a href="{{ route($route) }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition {{ $active ? 'nav-active' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                <x-icon :name="$icon" class="w-[18px] h-[18px] shrink-0" /><span class="truncate">{{ __($label) }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
                @endif
            @endforeach
        </nav>
        <a href="{{ route('account.edit') }}" class="border-t border-white/10 p-4 flex items-center gap-3 hover:bg-white/5 transition" title="{{ __('Mening profilim') }}">
            <div class="h-9 w-9 rounded-full bg-white/10 grid place-items-center text-sm font-semibold text-white overflow-hidden shrink-0">@if($u->avatarUrl())<img src="{{ $u->avatarUrl() }}" class="h-full w-full object-cover" alt="">@else{{ $u->initial() }}@endif</div>
            <div class="min-w-0 text-xs flex-1"><div class="font-medium text-white truncate">{{ $u->name }}</div><div class="text-slate-400 truncate">{{ $u->isSuper() ? 'Super Admin' : $u->roleLabel() }}</div></div>
            <x-icon name="settings" class="w-4 h-4 text-slate-500" />
        </a>
    </aside>
    <div x-show="open" x-cloak @click="open = false" class="fixed inset-0 z-30 bg-slate-900/50 backdrop-blur-sm md:hidden"></div>

    <div class="flex-1 min-w-0">
        <header class="sticky top-0 z-20 h-16 bg-white/80 backdrop-blur border-b border-slate-200/70 flex items-center justify-between px-4 md:px-8">
            <div class="flex items-center gap-3 min-w-0">
                <button class="md:hidden btn btn-sm !px-2" @click="open = !open" aria-label="Menu"><x-icon name="menu" class="w-5 h-5" /></button>
                <h1 class="font-semibold text-lg text-slate-900 truncate">@yield('title')</h1>
            </div>
            <div class="flex items-center gap-2 sm:gap-3 text-sm">
                @unless($isAdminArea)
                    <button type="button" @click="$dispatch('open-search')" class="hidden md:flex items-center gap-2 rounded-xl border border-slate-200 bg-white/70 hover:bg-white pl-3 pr-2 py-1.5 text-slate-400 w-56 lg:w-72 transition" aria-label="{{ __('Qidirish') }}">
                        <x-icon name="search" class="w-4 h-4" /><span class="flex-1 text-left text-sm">{{ __('Tezkor qidiruv...') }}</span><kbd class="text-[10px] border border-slate-200 rounded px-1.5 py-0.5">Ctrl K</kbd>
                    </button>
                    <button type="button" @click="$dispatch('open-search')" class="md:hidden btn btn-sm !px-2" aria-label="{{ __('Qidirish') }}"><x-icon name="search" class="w-4 h-4" /></button>
                @endunless
                @include('partials.pwa-install')
                @include('partials.bell')
                <div class="flex rounded-lg bg-slate-100 p-0.5 text-xs font-semibold">
                    <a href="{{ route('lang', 'uz') }}" class="px-2.5 py-1 rounded-md {{ app()->getLocale() === 'uz' ? 'bg-white shadow text-brand' : 'text-slate-500' }}">UZ</a>
                    <a href="{{ route('lang', 'ru') }}" class="px-2.5 py-1 rounded-md {{ app()->getLocale() === 'ru' ? 'bg-white shadow text-brand' : 'text-slate-500' }}">RU</a>
                </div>
                <button type="button" onclick="toggleTheme()" class="btn btn-sm !px-2" title="{{ __('Qorong\'i / yorug\' rejim') }}" aria-label="Theme">
                    <x-icon name="moon" class="w-4 h-4 dark:hidden" /><x-icon name="sun" class="w-4 h-4 hidden dark:block" />
                </button>
                <div x-data="{ m: false }" class="relative" @keydown.escape.window="m = false">
                    <button type="button" @click="m = !m" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white/70 hover:bg-white pl-1 pr-2.5 py-1 transition" aria-label="{{ __('Profil') }}">
                        <span class="h-7 w-7 rounded-full bg-brand text-white grid place-items-center text-xs font-bold overflow-hidden">@if($u->avatarUrl())<img src="{{ $u->avatarUrl() }}" class="h-full w-full object-cover" alt="">@else{{ $u->initial() }}@endif</span>
                        <span class="hidden lg:block text-xs font-medium max-w-[8rem] truncate">{{ \Illuminate\Support\Str::of($u->name)->explode(' ')->first() }}</span>
                        <x-icon name="chevron-down" class="w-3.5 h-3.5 text-slate-400" />
                    </button>
                    <div x-show="m" x-cloak x-transition.origin.top.right @click.outside="m = false" class="absolute right-0 mt-2 w-64 card shadow-xl overflow-hidden z-50">
                        <div class="px-4 py-3 border-b border-slate-100"><div class="font-semibold truncate">{{ $u->name }}</div><div class="text-xs text-slate-500 truncate">{{ $u->isSuper() ? 'Super Admin' : $u->roleLabel() }}</div></div>
                        <a href="{{ route('account.edit') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-slate-50"><x-icon name="user" class="w-4 h-4 text-slate-400" />{{ __('Mening profilim') }}</a>
                        <a href="{{ route('account.edit') }}#password" class="flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-slate-50"><x-icon name="lock" class="w-4 h-4 text-slate-400" />{{ __('Parolni yangilash') }}</a>
                        <button type="button" @click="m = false; $dispatch('confirm-logout')" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 border-t border-slate-100"><x-icon name="logout" class="w-4 h-4" />{{ __('Chiqish') }}</button>
                    </div>
                </div>
            </div>
        </header>

        @include('partials.pwa-install', ['mode' => 'banner'])
        {{-- Holat bannerlari --}}
        @if(! $isAdminArea && $u->isSuper() && session('impersonate_clinic_id'))
            <div class="bg-purple-600 text-white text-sm px-4 md:px-8 py-2 flex items-center justify-between gap-3">
                <span>{{ __('Siz Super Admin sifatida klinika ichidasiz:') }} <b>{{ $clinic->name ?? '' }}</b></span>
                <form method="POST" action="{{ route('app.impersonate.stop') }}">@csrf<button class="underline">{{ __('Chiqish') }}</button></form>
            </div>
        @endif
        @if(! $isAdminArea && isset($clinic))
            @if($clinic->status === 'trial')
                <div class="hero-grad text-white text-sm px-4 md:px-8 py-2 flex flex-wrap items-center justify-between gap-2">
                    <span class="inline-flex items-center gap-2"><x-icon name="flask-conical" class="w-4 h-4" /> {{ __('Demo versiya') }} — <b>{{ __(':n kun qoldi', ['n' => $clinic->trialDaysLeft()]) }}</b>. {{ __("Demo tugagach obuna olinmasa, ma'lumotlar o'chiriladi.") }}</span>
                    @if($u->isDirector() || $u->hasRole('clinic_admin'))<a href="{{ route('app.billing') }}" class="rounded bg-white/20 hover:bg-white/30 px-3 py-1 font-medium">{{ __("Obunaga o'tish") }}</a>@endif
                </div>
            @elseif($clinic->status === 'view_only')
                <div class="bg-orange-500 text-white text-sm px-4 md:px-8 py-2 flex flex-wrap items-center justify-between gap-2">
                    <span class="inline-flex items-center gap-2"><x-icon name="alert" class="w-4 h-4" /> {{ __("Obuna to'lanmagan: tizim faqat ko'rish rejimida.") }}</span>
                    @if($u->isDirector() || $u->hasRole('clinic_admin'))<a href="{{ route('app.billing') }}" class="rounded bg-white/20 px-3 py-1 font-medium">{{ __("Balansni to'ldirish") }}</a>@endif
                </div>
            @elseif($clinic->status === 'blocked')
                <div class="bg-red-600 text-white text-sm px-4 md:px-8 py-2 flex items-center gap-2"><x-icon name="alert" class="w-4 h-4" /> {{ __("Klinika bloklangan. Balansni to'ldiring, aks holda ma'lumotlar o'chiriladi.") }}</div>
            @endif
        @endif

        <main class="p-4 md:p-8 max-w-[1500px]">
            <x-flash />
            @yield('content')
        </main>
    </div>
</div>
@unless($isAdminArea)@include('partials.search')@endunless
@include('partials.pwa-install', ['mode' => 'ios'])
@include('partials.logout-confirm')
@stack('scripts')
</body>
</html>
