@extends('layouts.app')
@section('title', __('JSHSHIR xizmati'))
@section('content')
@php $on = (bool) $clinic->setting('pinfl_enabled'); @endphp
<div class="max-w-4xl space-y-5">
    <div class="card p-5 flex items-start gap-4">
        <span class="h-11 w-11 rounded-xl grid place-items-center {{ $on ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-500' }}"><x-icon name="shield" class="w-5 h-5" /></span>
        <div class="text-sm flex-1">
            <div class="font-semibold text-slate-900">{{ $on ? __('JSHSHIR xizmati yoqilgan') : __('JSHSHIR xizmati o\'chirilgan') }}</div>
            <p class="text-slate-500 mt-1">{{ __("Bemorning JSHSHIR raqamini kiritganda F.I.Sh., tug'ilgan sana va jinsi avtomatik to'ldiriladi. Bu xizmatni taqdim etuvchi tashkilot bilan shartnomani klinikaning o'zi tuzadi; shartnoma bo'yicha olingan API ma'lumotlarini quyida kiriting.") }}</p>
            <p class="text-slate-500 mt-1">{{ __("Shaxsiy ma'lumotlarni olish huquqi klinikaning shartnomasiga bog'liq; so'rovlar jurnalga yoziladi (JSHSHIR maskalangan holda).") }}</p>
        </div>
        <div class="text-right text-xs text-slate-500 shrink-0">
            <div class="font-semibold text-slate-700">{{ __('Shu oy so\'rovlar') }}</div>
            <div class="mt-1">✓ {{ $stats['ok'] }} · ∅ {{ $stats['nf'] }} · ✗ {{ $stats['err'] }}</div>
        </div>
    </div>

    <form method="POST" action="{{ route('app.pinfl.update') }}" class="card p-6 space-y-5">
        @csrf @method('PUT')
        <x-field name="pinfl_enabled" type="checkbox" :label="__('Xizmatni yoqish')" :value="$on" />

        <div class="grid md:grid-cols-4 gap-4">
            <div class="md:col-span-3"><x-field name="pinfl_url" :label="__('API manzili (URL)')" :value="$clinic->setting('pinfl_url')" placeholder="https://api.provayder.uz/v1/person/{pinfl}" :hint="__('JSHSHIR o\'rniga {pinfl} yozing. Faqat https://')" /></div>
            <x-field name="pinfl_method" type="select" :label="__('Usul')" :value="$clinic->setting('pinfl_method', 'GET')" :options="['GET' => 'GET', 'POST' => 'POST']" />
        </div>

        <div class="grid md:grid-cols-3 gap-4">
            <x-field name="pinfl_auth" type="select" :label="__('Avtorizatsiya')" :value="$clinic->setting('pinfl_auth', 'none')" :options="['none' => __('Yo\'q'), 'bearer' => 'Bearer token', 'header' => __('Maxsus sarlavha (header)'), 'basic' => 'Basic (login/parol)', 'query' => __('URL parametri')]" />
            <x-field name="pinfl_auth_name" :label="__('Sarlavha / login / parametr nomi')" :value="$clinic->setting('pinfl_auth_name')" :hint="__('Masalan: X-Api-Key yoki login')" />
            <x-field name="pinfl_secret" type="password" :label="__('Token / parol / API kalit')" :hint="$hasSecret ? __('Saqlangan. O\'zgartirmaslik uchun bo\'sh qoldiring') : null" />
        </div>

        <x-field name="pinfl_body" type="textarea" rows="2" :label="__('So\'rov tanasi (faqat POST uchun, JSON)')" :value="$clinic->setting('pinfl_body')" placeholder='{"pinfl":"{pinfl}"}' />

        <div>
            <div class="text-sm font-semibold text-slate-800 mb-1">{{ __('Javob maydonlari xaritasi') }}</div>
            <p class="text-xs text-slate-500 mb-3">{{ __("Provayder javobidagi maydon yo'llarini yozing (ichma-ich bo'lsa nuqta bilan: person.last_name). Bo'sh qoldirilgan maydon to'ldirilmaydi.") }}</p>
            <div class="grid md:grid-cols-3 gap-4">
                <x-field name="pinfl_root" :label="__('Ma\'lumotlar joylashgan tugun (ixtiyoriy)')" :value="$clinic->setting('pinfl_root')" placeholder="data" />
                @foreach(['last_name' => 'Familiya', 'first_name' => 'Ism', 'middle_name' => 'Otasining ismi', 'birth_date' => "Tug'ilgan sana", 'gender' => 'Jinsi', 'address' => 'Manzil'] as $k => $l)
                    <x-field :name="'map['.$k.']'" :label="__($l)" :value="$map[$k] ?? ''" />
                @endforeach
            </div>
        </div>
        <button class="btn btn-primary"><x-icon name="check" class="w-4 h-4" />{{ __('Saqlash') }}</button>
    </form>

    <form method="POST" action="{{ route('app.pinfl.test') }}" class="card p-6">
        @csrf
        <div class="font-semibold mb-3 flex items-center gap-2"><x-icon name="activity" class="w-4 h-4 text-brand" />{{ __('Sinab ko\'rish') }}</div>
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[240px]"><x-field name="test_pinfl" :label="__('JSHSHIR (14 raqam)')" attrs='inputmode="numeric" maxlength="14"' /></div>
            <button class="btn"><x-icon name="search" class="w-4 h-4" />{{ __('Tekshirish') }}</button>
        </div>
        <p class="text-xs text-slate-500 mt-2">{{ __("Avval yuqoridagi sozlamalarni saqlang, so'ng sinang.") }}</p>
        @if($t = session('pinfl_test'))
            <div class="mt-4 rounded-xl border border-slate-200 p-4 text-sm">
                @if($t['status'] === 'ok')
                    <div class="font-medium text-emerald-700 mb-2">{{ __('Topildi — xarita bo\'yicha olingan maydonlar:') }}</div>
                    <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-1">@foreach($t['data'] as $k => $v)<div class="flex justify-between gap-3"><dt class="text-slate-500">{{ $k }}</dt><dd class="font-medium">{{ $v ?: '—' }}</dd></div>@endforeach</dl>
                @else
                    <div class="font-medium text-amber-700">{{ __('Ma\'lumot topilmadi yoki xarita noto\'g\'ri.') }}</div>
                @endif
                @if(! empty($t['keys']))<div class="mt-3 text-xs text-slate-500"><div class="font-medium mb-1">{{ __('Javobdagi maydon yo\'llari:') }}</div><code class="break-all">{{ implode('  ·  ', $t['keys']) }}</code></div>@endif
            </div>
        @endif
    </form>
</div>
@endsection
