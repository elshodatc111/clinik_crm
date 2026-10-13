@extends('layouts.public')
@section('title', $brand['name'].' — '.__('Klinika uchun CRM tizimi'))
@section('content')
@php
$features = [
    ['users', 'Bemorlar bazasi', "Har bir bemor faqat bir marta kiritiladi. JSHSHIR bo'yicha dublikatlardan himoya, to'liq tibbiy tarix."],
    ['calendar', 'Qabul kalendari', "Shifokorlar bo'yicha kunlik va haftalik jadval, band vaqt tekshiruvi, SMS eslatma."],
    ['stethoscope', 'Elektron tibbiy karta', "Shikoyat, anamnez, tashxis, retsept va xulosani klinika blankasida chop eting."],
    ['receipt', 'Kassa va qarzlar', "Naqd, karta, Payme va Click to'lovlari, qisman to'lov, chegirma, qarzdorlar ro'yxati."],
    ['flask', 'Laboratoriya', "Tahlil yo'llanmasi, natija va fayl yuklash, tayyor bo'lganda SMS."],
    ['chart', 'Hisobotlar', "Tushum, shifokorlar va xizmatlar bo'yicha diagrammalar, reklama manbalari."],
    ['banknote', 'Ish haqi hisobi', "Oylik, kunlik, foiz va aralash tizimlar; avans, mukofot va jarima."],
    ['shield', "Har bir klinika alohida", "Klinikangiz ma'lumotlari boshqa klinikalardan to'liq ajratilgan va xavfsiz."],
    ['activity', "O'zbekcha va ruscha", "Interfeys ikki tilda ishlaydi, qorong'i rejim ham bor."],
];
$stats = [['14', 'kunlik bepul demo'], ['5', 'xodim roli'], ['24/7', 'onlayn qabul'], ['UZ·RU', 'ikki tilda']];
$showcase = [
    ['booking', 'smartphone', "Onlayn qabul va to'lov", "Har bir klinikaning o'z sahifasi: bemor xizmat va shifokorni tanlaydi, to'lovni Payme orqali qiladi va avtomatik navbatga yoziladi. Pul to'g'ridan-to'g'ri klinikaning o'z kassasiga tushadi.", ["Klinikaning o'z Payme kassasi", "Navbat raqami va SMS tasdiq", "Band vaqt avtomatik yopiladi"]],
    ['cashier', 'banknote', "Kassa, maosh va hisobotlar", "Kunlik kassa usullar bo'yicha (naqd, karta, Payme) ko'rinadi, kun yopiladi va farq hisoblanadi. Ish haqi xodimlar bo'yicha avtomatik chiqadi.", ["Kunni yopish va naqd farqi", "Barcha xodimlar uchun ish haqi", "Chop etiladigan hisobotlar"]],
    ['admin', 'shield', "Rollar va xavfsizlik", "Har bir xodim o'z login-paroli bilan kiradi va faqat o'z bo'limlarini ko'radi. Qo'shimcha ruxsatlarni direktor boshqaradi, har bir harakat jurnalga yoziladi.", ["Direktor, admin, shifokor, kassir, qabulxona, laborant", "Har bir rol uchun yo'riqnoma (PDF)", "Harakatlar jurnali"]],
];
$faq = [
    ['Demo versiya qancha vaqt ishlaydi?', ":n kun davomida barcha imkoniyatlardan bepul foydalanasiz. Karta talab qilinmaydi."],
    ["Demo tugagach nima bo'ladi?", "Obunaga o'tsangiz, ma'lumotlaringiz saqlanib qoladi. O'tmasangiz, oldindan SMS orqali ogohlantiramiz va demo ma'lumotlari o'chiriladi."],
    ["Narx qanday hisoblanadi?", "Narx bazangizdagi bemorlar soniga qarab tarif bo'yicha belgilanadi. Bir martalik ulanish to'lovi bor, ulanishdan keyingi birinchi oy bepul."],
    ["To'lov qanday amalga oshiriladi?", "Balansingizni Payme orqali to'ldirasiz, oylik to'lov balansdan avtomatik yechiladi."],
    ["Onlayn qabul to'lovi kimga tushadi?", "Bemorning to'lovi bevosita klinikaning o'z Payme kassasiga tushadi. Platforma bu pulga aralashmaydi."],
    ["Ma'lumotlarim xavfsizmi?", "Har bir klinika ma'lumotlari boshqalardan ajratilgan, barcha muhim harakatlar jurnalga yoziladi, zaxira nusxalar saqlanadi."],
];
@endphp

{{-- HERO --}}
<section class="relative overflow-hidden bg-gradient-to-b from-blue-50 via-white to-white">
    <div class="absolute -top-32 -right-32 h-[28rem] w-[28rem] rounded-full bg-brand opacity-10 blur-3xl"></div>
    <div class="absolute -bottom-20 -left-20 h-72 w-72 rounded-full bg-cyan-400 opacity-10 blur-3xl"></div>
    <div class="relative max-w-6xl mx-auto px-4 py-14 md:py-24 grid md:grid-cols-2 gap-10 items-center">
        <div>
            <span class="inline-flex items-center gap-2 rounded-full bg-brand-soft text-brand px-3 py-1 text-xs font-semibold"><x-icon name="heart-pulse" class="w-4 h-4" />{{ __('Klinikalar uchun zamonaviy boshqaruv tizimi') }}</span>
            <h1 class="mt-4 text-4xl md:text-6xl font-extrabold leading-[1.08] tracking-tight text-slate-900">{{ __("Klinikangizni to'liq avtomatlashtiring") }}</h1>
            <p class="mt-5 text-lg text-slate-600 max-w-xl">{{ __("Bemorlar, qabul, tibbiy karta, kassa, tahlillar va onlayn yozilish — barchasi bitta tizimda. Qog'oz va Excel bilan ishlashga chek qo'ying.") }}</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('register') }}" class="btn btn-primary text-base px-6 py-3"><x-icon name="check" class="w-5 h-5" />{{ __(':n kunlik bepul demo', ['n' => $demoDays]) }}</a>
                <a href="{{ route('landing') }}#contact" class="btn text-base px-6 py-3"><x-icon name="phone" class="w-5 h-5" />{{ __("Bog'lanish") }}</a>
            </div>
            <div class="mt-6 flex flex-wrap gap-x-5 gap-y-2 text-sm text-slate-500">
                <span class="inline-flex items-center gap-1.5"><x-icon name="badge-check" class="w-4 h-4 text-emerald-500" />{{ __('Karta kerak emas') }}</span>
                <span class="inline-flex items-center gap-1.5"><x-icon name="badge-check" class="w-4 h-4 text-emerald-500" />{{ __("Ro'yxatdan o'tish 2 daqiqa") }}</span>
                <span class="inline-flex items-center gap-1.5"><x-icon name="badge-check" class="w-4 h-4 text-emerald-500" />{{ __("O'zbekcha va ruscha") }}</span>
            </div>
        </div>
        <div class="relative">
            <div class="aspect-[4/3] rounded-[2rem] bg-white shadow-2xl shadow-blue-900/10 border border-slate-100 p-4"><x-illu name="hero" /></div>
            <div class="absolute -bottom-5 -left-4 md:-left-8 card p-3 pr-5 flex items-center gap-3 shadow-xl">
                <span class="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-600 grid place-items-center"><x-icon name="check" class="w-5 h-5" /></span>
                <div><div class="text-sm font-semibold">{{ __('Navbat: №7') }}</div><div class="text-xs text-slate-500">{{ __("To'lov qabul qilindi") }}</div></div>
            </div>
        </div>
    </div>
</section>

{{-- STATS --}}
<section class="border-y border-slate-100 bg-white">
    <div class="max-w-6xl mx-auto px-4 py-8 grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
        @foreach($stats as [$v, $l])<div><div class="text-3xl font-extrabold text-brand">{{ $v }}</div><div class="text-sm text-slate-500 mt-1">{{ __($l) }}</div></div>@endforeach
    </div>
</section>

{{-- FEATURES --}}
<section class="max-w-6xl mx-auto px-4 py-16 md:py-20" id="features">
    <div class="text-center max-w-2xl mx-auto"><h2 class="text-3xl md:text-4xl font-bold">{{ __('Nimalarga imkon beradi') }}</h2><p class="mt-3 text-slate-600">{{ __("Klinikaning kundalik ishi uchun kerak bo'lgan hamma narsa bir joyda.") }}</p></div>
    <div class="mt-10 grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($features as [$icon, $title, $text])
            <div class="card p-6 transition hover:-translate-y-1 hover:shadow-lg">
                <span class="h-12 w-12 rounded-xl bg-brand-soft text-brand grid place-items-center"><x-icon :name="$icon" class="w-6 h-6" /></span>
                <div class="mt-3 font-semibold">{{ __($title) }}</div>
                <p class="mt-1 text-sm text-slate-600">{{ __($text) }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- SHOWCASE --}}
<section class="bg-slate-50 py-16 md:py-20">
    <div class="max-w-6xl mx-auto px-4 space-y-16 md:space-y-24">
        @foreach($showcase as $i => [$illu, $icon, $title, $text, $points])
            <div class="grid md:grid-cols-2 gap-10 items-center">
                <div class="{{ $i % 2 ? 'md:order-2' : '' }}">
                    <span class="h-12 w-12 rounded-xl bg-brand text-white grid place-items-center"><x-icon :name="$icon" class="w-6 h-6" /></span>
                    <h3 class="mt-4 text-2xl md:text-3xl font-bold">{{ __($title) }}</h3>
                    <p class="mt-3 text-slate-600">{{ __($text) }}</p>
                    <ul class="mt-5 space-y-2.5">
                        @foreach($points as $pt)<li class="flex items-start gap-2.5 text-slate-700"><span class="mt-0.5 h-5 w-5 rounded-full bg-emerald-100 text-emerald-600 grid place-items-center shrink-0"><x-icon name="check" class="w-3.5 h-3.5" /></span>{{ __($pt) }}</li>@endforeach
                    </ul>
                </div>
                <div class="rounded-[2rem] bg-white border border-slate-100 shadow-xl shadow-blue-900/5 p-6 aspect-[4/3]"><x-illu :name="$illu" /></div>
            </div>
        @endforeach
    </div>
</section>

{{-- HOW --}}
<section class="max-w-6xl mx-auto px-4 py-16 md:py-20" id="how">
    <h2 class="text-3xl md:text-4xl font-bold text-center">{{ __('Qanday boshlanadi') }}</h2>
    <div class="mt-10 grid md:grid-cols-3 gap-5 relative">
        @foreach([['1', "Ro'yxatdan o'ting", "Klinika nomi va telefon raqamingizni kiriting, SMS kod bilan tasdiqlang.", 'user-plus'], ['2', "Demo bilan tanishing", "Klinika ma'lumotlari, xodimlar, xizmatlarni kiriting va ishlab ko'ring.", 'activity'], ['3', "Obunaga o'ting", "Balansni Payme orqali to'ldiring va ish davom etadi.", 'wallet']] as [$n, $t, $d, $ic])
            <div class="card p-6 relative">
                <div class="flex items-center justify-between"><div class="h-10 w-10 rounded-full bg-brand text-white grid place-items-center font-bold">{{ $n }}</div><x-icon :name="$ic" class="w-8 h-8 text-slate-300" /></div>
                <div class="mt-4 font-semibold text-lg">{{ __($t) }}</div><p class="mt-1 text-sm text-slate-600">{{ __($d) }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- PRICING --}}
<section class="bg-slate-50 py-16 md:py-20" id="pricing">
    <div class="max-w-6xl mx-auto px-4">
        <h2 class="text-3xl md:text-4xl font-bold text-center">{{ __('Tariflar') }}</h2>
        <p class="text-center text-slate-600 mt-3">{{ __("Narx bazadagi bemorlar soniga qarab belgilanadi. Bir martalik ulanish to'lovi:") }} <b>{{ \App\Support\Tenant::money($setupFee) }}</b> ({{ __('birinchi oy bepul') }})</p>
        <div class="mt-10 grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach($plans as $i => $p)
                <div class="card p-6 text-center transition hover:-translate-y-1 hover:shadow-lg {{ $i === 1 ? 'ring-2 ring-[var(--brand)]' : '' }}">
                    @if($i === 1)<div class="-mt-9 mb-3"><span class="inline-flex rounded-full bg-brand text-white text-xs font-semibold px-3 py-1">{{ __('Mashhur') }}</span></div>@endif
                    <div class="font-semibold text-lg">{{ $p->name }}</div>
                    <div class="mt-1 text-sm text-slate-500">{{ $p->rangeLabel() }} {{ __('bemor') }}</div>
                    <div class="mt-5 text-3xl font-extrabold text-brand">{{ number_format($p->monthly_price, 0, '.', ' ') }}</div>
                    <div class="text-sm text-slate-500">{{ __("so'm / oy") }}</div>
                    <ul class="mt-5 space-y-2 text-sm text-slate-600 text-left">
                        @foreach(["Barcha modullar", "Cheksiz xodim", "Onlayn qabul sahifasi", "SMS eslatmalar"] as $f)<li class="flex items-center gap-2"><x-icon name="check" class="w-4 h-4 text-emerald-500" />{{ __($f) }}</li>@endforeach
                    </ul>
                    <a href="{{ route('register') }}" class="btn {{ $i === 1 ? 'btn-primary' : '' }} mt-6 w-full">{{ __('Boshlash') }}</a>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- FAQ --}}
<section class="max-w-3xl mx-auto px-4 py-16 md:py-20" id="faq">
    <h2 class="text-3xl md:text-4xl font-bold text-center">{{ __('Ko\'p beriladigan savollar') }}</h2>
    <div class="mt-8 space-y-3">
        @foreach($faq as [$q, $a])
            <details class="card p-4 group"><summary class="font-medium cursor-pointer flex items-center justify-between gap-3">{{ __($q) }}<span class="transition group-open:rotate-180 text-slate-400"><x-icon name="chevron-down" class="w-5 h-5" /></span></summary><p class="mt-3 text-sm text-slate-600">{{ __($a, ['n' => $demoDays]) }}</p></details>
        @endforeach
    </div>
</section>

{{-- CONTACT --}}
@php
    $ct = $brand['contact'] ?? [];
    $hasContact = collect($ct)->filter()->isNotEmpty();
    $tg = ! empty($ct['telegram']) ? (str_starts_with($ct['telegram'], 'http') ? $ct['telegram'] : 'https://t.me/'.ltrim($ct['telegram'], '@')) : null;
    $ig = ! empty($ct['instagram']) ? (str_starts_with($ct['instagram'], 'http') ? $ct['instagram'] : 'https://instagram.com/'.ltrim($ct['instagram'], '@')) : null;
@endphp
<section class="bg-slate-50 py-16 md:py-20" id="contact">
    <div class="max-w-6xl mx-auto px-4">
        <div class="text-center max-w-2xl mx-auto"><h2 class="text-3xl md:text-4xl font-bold">{{ __("Bog'lanish") }}</h2><p class="mt-3 text-slate-600">{{ __("Savollaringiz bormi? Demo ko'rsatib beramiz va tizimni klinikangizga moslashtirishda yordam beramiz.") }}</p></div>
        <div class="mt-10 grid lg:grid-cols-2 gap-6">
            <div class="card p-6 md:p-8 space-y-5">
                @if(! $hasContact)<p class="text-slate-500">{{ __("Aloqa ma'lumotlari tez orada qo'shiladi.") }}</p>@endif
                @if(! empty($ct['phone']) || ! empty($ct['phone2']))
                    <div class="flex items-start gap-4"><span class="h-11 w-11 rounded-xl bg-brand-soft text-brand grid place-items-center shrink-0"><x-icon name="phone" class="w-5 h-5" /></span><div><div class="text-xs text-slate-500">{{ __('Telefon') }}</div>
                        @foreach(array_filter([$ct['phone'] ?? null, $ct['phone2'] ?? null]) as $ph)<a class="block font-semibold text-slate-900 hover:text-brand" href="tel:{{ preg_replace('/[^+\d]/', '', $ph) }}">{{ $ph }}</a>@endforeach</div></div>
                @endif
                @if(! empty($ct['email']))
                    <div class="flex items-start gap-4"><span class="h-11 w-11 rounded-xl bg-brand-soft text-brand grid place-items-center shrink-0"><x-icon name="mail" class="w-5 h-5" /></span><div><div class="text-xs text-slate-500">Email</div><a class="font-semibold text-slate-900 hover:text-brand" href="mailto:{{ $ct['email'] }}">{{ $ct['email'] }}</a></div></div>
                @endif
                @if(! empty($ct['address']))
                    <div class="flex items-start gap-4"><span class="h-11 w-11 rounded-xl bg-brand-soft text-brand grid place-items-center shrink-0"><x-icon name="map-pin" class="w-5 h-5" /></span><div><div class="text-xs text-slate-500">{{ __('Manzil') }}</div><div class="font-semibold text-slate-900">{{ $ct['address'] }}</div></div></div>
                @endif
                @if(! empty($ct['hours']))
                    <div class="flex items-start gap-4"><span class="h-11 w-11 rounded-xl bg-brand-soft text-brand grid place-items-center shrink-0"><x-icon name="clock" class="w-5 h-5" /></span><div><div class="text-xs text-slate-500">{{ __('Ish vaqti') }}</div><div class="font-semibold text-slate-900">{{ $ct['hours'] }}</div></div></div>
                @endif
                <div class="flex flex-wrap gap-3 pt-2">
                    @if($tg)<a href="{{ $tg }}" target="_blank" rel="noopener" class="btn"><x-icon name="send" class="w-4 h-4" />Telegram</a>@endif
                    @if($ig)<a href="{{ $ig }}" target="_blank" rel="noopener" class="btn"><x-icon name="globe" class="w-4 h-4" />Instagram</a>@endif
                    <a href="{{ route('register') }}" class="btn btn-primary">{{ __('Bepul demoni boshlash') }}</a>
                </div>
            </div>
            <div class="card overflow-hidden min-h-[320px]">
                @if(! empty($ct['map']))
                    <iframe src="{{ $ct['map'] }}" class="w-full h-full min-h-[320px] border-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                @else
                    <div class="h-full min-h-[320px] bg-brand-soft p-8 grid place-items-center"><div class="w-64"><x-illu name="reception" /></div></div>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="hero-grad text-white">
    <div class="max-w-4xl mx-auto px-4 py-14 text-center">
        <h2 class="text-3xl md:text-4xl font-bold">{{ __('Klinikangizni bugunoq zamonaviylashtiring') }}</h2>
        <p class="mt-3 text-white/80">{{ __(':n kun bepul sinab ko\'ring. Karta talab qilinmaydi.', ['n' => $demoDays]) }}</p>
        <a href="{{ route('register') }}" class="btn !bg-white !text-slate-900 border-transparent mt-6 text-base px-8 py-3">{{ __('Bepul demoni boshlash') }}</a>
    </div>
</section>
@endsection
