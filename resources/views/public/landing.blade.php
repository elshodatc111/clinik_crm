@extends('layouts.public')
@section('title', $brand['name'].' — '.__('Klinika uchun CRM tizimi'))
@section('content')
@php
$features = [
    ['users', 'Bemorlar bazasi', "Har bir bemor faqat bir marta kiritiladi. JSHSHIR bo'yicha dublikatlardan himoya, to'liq tibbiy tarix."],
    ['calendar', 'Qabul jadvali', "Shifokorlar bo'yicha kunlik jadval, band vaqt tekshiruvi, SMS eslatma."],
    ['stethoscope', 'Elektron tibbiy karta', "Shikoyat, anamnez, tashxis (MKB-10), retsept — bemor kartasida barchasi."],
    ['receipt', 'Kassa va qarzlar', "Hisob-fakturalar, qisman to'lov, chegirma, chek chop etish, qarzdorlar ro'yxati."],
    ['flask', 'Laboratoriya', "Tahlil yo'llanmasi, natija va fayl yuklash, tayyor bo'lganda SMS."],
    ['chart', 'Hisobotlar', "Tushum, shifokorlar va xizmatlar bo'yicha tahlil, reklama manbalari."],
    ['shield', "Har bir klinika alohida", "Klinikangiz ma'lumotlari boshqa klinikalardan to'liq ajratilgan va xavfsiz."],
    ['message', 'SMS xabarnomalar', "Eslatmalar va natijalar bemorlarga avtomatik yuboriladi."],
    ['activity', "O'zbekcha va ruscha", "Interfeys ikki tilda ishlaydi."],
];
$faq = [
    ['Demo versiya qancha vaqt ishlaydi?', ":n kun davomida barcha imkoniyatlardan bepul foydalanasiz. Karta talab qilinmaydi."],
    ["Demo tugagach nima bo'ladi?", "Obunaga o'tsangiz, ma'lumotlaringiz saqlanib qoladi. O'tmasangiz, oldindan SMS orqali ogohlantiramiz va demo ma'lumotlari o'chiriladi."],
    ["Narx qanday hisoblanadi?", "Narx bazangizdagi bemorlar soniga qarab tarif bo'yicha belgilanadi. Bir martalik ulanish to'lovi bor, ulanishdan keyingi birinchi oy bepul."],
    ["To'lov qanday amalga oshiriladi?", "Balansingizni Payme orqali to'ldirasiz, oylik to'lov balansdan avtomatik yechiladi."],
    ["Ma'lumotlarim xavfsizmi?", "Har bir klinika ma'lumotlari boshqalardan ajratilgan, barcha muhim harakatlar jurnalga yoziladi, zaxira nusxalar saqlanadi."],
];
@endphp

<section class="relative overflow-hidden bg-gradient-to-b from-blue-50 via-white to-white"><div class="absolute -top-24 -right-24 h-96 w-96 rounded-full bg-brand opacity-10 blur-3xl"></div>
    <div class="max-w-6xl mx-auto px-4 py-16 md:py-24 grid md:grid-cols-2 gap-10 items-center">
        <div>
            <h1 class="text-4xl md:text-6xl font-extrabold leading-[1.1] tracking-tight text-slate-900">{{ __("Klinikangizni to'liq avtomatlashtiring") }}</h1>
            <p class="mt-5 text-lg text-slate-600">{{ __("Bemorlar, qabul, tibbiy karta, kassa, tahlillar va hisobotlar — barchasi bitta tizimda. Qog'oz va Excel bilan ishlashga chek qo'ying.") }}</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('register') }}" class="btn btn-primary text-base px-6 py-3">{{ __(':n kunlik bepul demo', ['n' => $demoDays]) }}</a>
                <a href="{{ route('login') }}" class="btn text-base px-6 py-3">{{ __('Kirish') }}</a>
            </div>
            <p class="mt-3 text-sm text-slate-500">{{ __("Ro'yxatdan o'tish 2 daqiqa oladi. Karta kerak emas.") }}</p>
        </div>
        <div class="card p-6 shadow-xl shadow-blue-900/5 md:rotate-1">
            <div class="text-sm font-medium text-slate-500 mb-3 flex items-center gap-2"><x-icon name="calendar" class="w-4 h-4" />{{ __('Bugungi qabullar') }}</div>
            @foreach([['09:00', 'Karimov A.', 'Terapevt', 'blue'], ['09:30', 'Yusupova D.', 'Kardiolog', 'green'], ['10:15', 'Abdullayev S.', 'Nevrolog', 'yellow'], ['11:00', 'Rahimova N.', 'LOR', 'slate']] as [$t, $n, $d, $c])
                <div class="flex items-center justify-between py-2 border-t border-slate-100 first:border-0">
                    <div><div class="font-medium">{{ $n }}</div><div class="text-xs text-slate-500">{{ $d }}</div></div>
                    <x-badge :color="$c">{{ $t }}</x-badge>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="max-w-6xl mx-auto px-4 py-16" id="features">
    <h2 class="text-3xl font-bold text-center">{{ __('Nimalarga imkon beradi') }}</h2>
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

<section class="bg-slate-50 py-16">
    <div class="max-w-6xl mx-auto px-4">
        <h2 class="text-3xl font-bold text-center">{{ __('Qanday boshlanadi') }}</h2>
        <div class="mt-10 grid md:grid-cols-3 gap-5">
            @foreach([['1', "Ro'yxatdan o'ting", "Klinika nomi va telefon raqamingizni kiriting, SMS kod bilan tasdiqlang."], ['2', "Demo bilan tanishing", "Klinika ma'lumotlari, xodimlar, xizmatlarni kiriting va ishlab ko'ring."], ['3', "Obunaga o'ting", "Balansni Payme orqali to'ldiring va ish davom etadi."]] as [$n, $t, $d])
                <div class="card p-5"><div class="h-9 w-9 rounded-full bg-brand text-white grid place-items-center font-bold">{{ $n }}</div><div class="mt-3 font-semibold">{{ __($t) }}</div><p class="mt-1 text-sm text-slate-600">{{ __($d) }}</p></div>
            @endforeach
        </div>
    </div>
</section>

<section class="max-w-6xl mx-auto px-4 py-16" id="pricing">
    <h2 class="text-3xl font-bold text-center">{{ __('Tariflar') }}</h2>
    <p class="text-center text-slate-600 mt-2">{{ __("Narx bazadagi bemorlar soniga qarab belgilanadi. Bir martalik ulanish to'lovi:") }} <b>{{ \App\Support\Tenant::money($setupFee) }}</b> ({{ __('birinchi oy bepul') }})</p>
    <div class="mt-10 grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
        @foreach($plans as $p)
            <div class="card p-6 text-center transition hover:-translate-y-1 hover:shadow-lg">
                <div class="font-semibold">{{ $p->name }}</div>
                <div class="mt-1 text-sm text-slate-500">{{ $p->rangeLabel() }} {{ __('bemor') }}</div>
                <div class="mt-4 text-3xl font-extrabold text-brand">{{ number_format($p->monthly_price, 0, '.', ' ') }}</div>
                <div class="text-sm text-slate-500">{{ __("so'm / oy") }}</div>
                <a href="{{ route('register') }}" class="btn btn-primary mt-5 w-full">{{ __('Boshlash') }}</a>
            </div>
        @endforeach
    </div>
</section>

<section class="max-w-3xl mx-auto px-4 pb-8" id="faq">
    <h2 class="text-3xl font-bold text-center">{{ __('Ko\'p beriladigan savollar') }}</h2>
    <div class="mt-8 space-y-3">
        @foreach($faq as [$q, $a])
            <details class="card p-4"><summary class="font-medium cursor-pointer">{{ __($q) }}</summary><p class="mt-2 text-sm text-slate-600">{{ __($a, ['n' => $demoDays]) }}</p></details>
        @endforeach
    </div>
    <div class="text-center mt-10"><a href="{{ route('register') }}" class="btn btn-primary text-base px-8 py-3">{{ __("Bepul demoni boshlash") }}</a></div>
</section>
@endsection
