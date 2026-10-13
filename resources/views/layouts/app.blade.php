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
    $isAdminArea = request()->routeIs('admin.*');
    $all = ['*'];
    if ($isAdminArea) {
        $nav = [
            ['admin.dashboard', 'Bosh sahifa', $all, 'home'],
            ['admin.clinics.index', 'Klinikalar', $all, 'building'],
            ['admin.plans.index', 'Tariflar', $all, 'layers'],
            ['admin.transactions.index', "To'lovlar va balans", $all, 'credit-card'],
            ['admin.patients.index', 'Bemorlar (o\'chirish)', $all, 'users'],
            ['admin.offers.index', 'Oferta', $all, 'file-text'],
            ['admin.settings.edit', 'Sozlamalar', $all, 'settings'],
            ['admin.audit.index', 'Harakatlar jurnali', $all, 'history'],
            ['admin.sms.index', 'SMS jurnali', $all, 'message'],
        ];
    } else {
        $nav = [
            ['app.dashboard', 'Bosh sahifa', $all, 'home'],
            ['app.patients.index', 'Bemorlar', ['clinic_admin', 'receptionist', 'doctor', 'cashier'], 'users'],
            ['app.calendar', 'Qabul kalendari', ['clinic_admin', 'receptionist', 'doctor'], 'calendar'],
            ['app.appointments.index', 'Qabullar', ['clinic_admin', 'receptionist', 'doctor'], 'clipboard'],
            ['app.visits.index', 'Tashriflar (tibbiy karta)', ['clinic_admin', 'doctor'], 'stethoscope'],
            ['app.invoices.index', 'Kassa va hisob-fakturalar', ['clinic_admin', 'receptionist', 'cashier'], 'receipt'],
            ['app.lab.index', 'Tahlillar', ['clinic_admin', 'receptionist', 'doctor', 'laborant'], 'flask'],
            ['app.reports.index', 'Hisobotlar', ['clinic_admin', 'cashier'], 'chart'],
            ['app.services.index', 'Xizmatlar va narxlar', ['clinic_admin'], 'tag'],
            ['app.staff.index', 'Xodimlar', ['clinic_admin'], 'user'],
            ['app.profile.edit', "Klinika ma'lumotlari", ['clinic_admin'], 'building'],
            ['app.billing', 'Obuna va balans', ['clinic_admin'], 'wallet'],
            ['app.audit.index', 'Harakatlar jurnali', ['clinic_admin'], 'history'],
            ['app.sms.settings', 'SMS sozlamalari', ['clinic_admin'], 'settings'],
            ['app.sms.index', 'SMS jurnali', ['clinic_admin'], 'message'],
        ];
    }
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
        <nav class="p-3 space-y-0.5 overflow-y-auto flex-1">
            @foreach($nav as [$route, $label, $roles, $icon])
                @if(in_array('*', $roles) || in_array($role, $roles) || $u->isSuper() && $isAdminArea)
                    @php($active = $route === 'app.sms.index' ? request()->routeIs('app.sms.index') : (request()->routeIs($route, preg_replace('/\.(index|edit)$/', '.*', $route)) || ($route === 'app.billing' && request()->routeIs('app.billing*'))))
                    <a href="{{ route($route) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ $active ? 'nav-active' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <x-icon :name="$icon" class="w-[18px] h-[18px]" /><span class="truncate">{{ __($label) }}</span>
                    </a>
                @endif
            @endforeach
        </nav>
        <div class="border-t border-white/10 p-4 flex items-center gap-3">
            <div class="h-9 w-9 rounded-full bg-white/10 grid place-items-center text-sm font-semibold text-white">{{ mb_strtoupper(mb_substr($u->name, 0, 1)) }}</div>
            <div class="min-w-0 text-xs"><div class="font-medium text-white truncate">{{ $u->name }}</div><div class="text-slate-400 truncate">{{ $isAdminArea ? 'Super Admin' : $u->roleLabel() }}</div></div>
        </div>
    </aside>
    <div x-show="open" x-cloak @click="open = false" class="fixed inset-0 z-30 bg-slate-900/50 backdrop-blur-sm md:hidden"></div>

    <div class="flex-1 min-w-0">
        <header class="sticky top-0 z-20 h-16 bg-white/80 backdrop-blur border-b border-slate-200/70 flex items-center justify-between px-4 md:px-8">
            <div class="flex items-center gap-3 min-w-0">
                <button class="md:hidden btn btn-sm !px-2" @click="open = !open" aria-label="Menu"><x-icon name="menu" class="w-5 h-5" /></button>
                <h1 class="font-semibold text-lg text-slate-900 truncate">@yield('title')</h1>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <div class="flex rounded-lg bg-slate-100 p-0.5 text-xs font-semibold">
                    <a href="{{ route('lang', 'uz') }}" class="px-2.5 py-1 rounded-md {{ app()->getLocale() === 'uz' ? 'bg-white shadow text-brand' : 'text-slate-500' }}">UZ</a>
                    <a href="{{ route('lang', 'ru') }}" class="px-2.5 py-1 rounded-md {{ app()->getLocale() === 'ru' ? 'bg-white shadow text-brand' : 'text-slate-500' }}">RU</a>
                </div>
                <button type="button" onclick="toggleTheme()" class="btn btn-sm !px-2" title="{{ __('Qorong\'i / yorug\' rejim') }}" aria-label="Theme">
                    <x-icon name="moon" class="w-4 h-4 dark:hidden" /><x-icon name="sun" class="w-4 h-4 hidden dark:block" />
                </button>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-sm"><x-icon name="logout" class="w-4 h-4" /><span class="hidden sm:inline">{{ __('Chiqish') }}</span></button></form>
            </div>
        </header>

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
                    @if($role === 'clinic_admin')<a href="{{ route('app.billing') }}" class="rounded bg-white/20 hover:bg-white/30 px-3 py-1 font-medium">{{ __("Obunaga o'tish") }}</a>@endif
                </div>
            @elseif($clinic->status === 'view_only')
                <div class="bg-orange-500 text-white text-sm px-4 md:px-8 py-2 flex flex-wrap items-center justify-between gap-2">
                    <span class="inline-flex items-center gap-2"><x-icon name="alert" class="w-4 h-4" /> {{ __("Obuna to'lanmagan: tizim faqat ko'rish rejimida.") }}</span>
                    @if($role === 'clinic_admin')<a href="{{ route('app.billing') }}" class="rounded bg-white/20 px-3 py-1 font-medium">{{ __("Balansni to'ldirish") }}</a>@endif
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
@stack('scripts')
</body>
</html>
