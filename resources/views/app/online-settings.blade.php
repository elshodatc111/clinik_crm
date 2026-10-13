@extends('layouts.app')
@section('title', __('Onlayn qabul'))
@section('content')
@php
    $endpoint = url('/payme/c/'.$clinic->slug);
    $days = [1 => 'Du', 2 => 'Se', 3 => 'Chor', 4 => 'Pay', 5 => 'Ju', 6 => 'Sha', 7 => 'Yak'];
    $selDays = array_map('intval', (array) $cfg('online_days'));
@endphp
<form method="POST" action="{{ route('app.online.update') }}" class="space-y-5 max-w-4xl" x-data="{ copied: '' }">
    @csrf @method('PUT')

    <div class="card p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="font-semibold flex items-center gap-2"><x-icon name="calendar" class="w-4 h-4 text-brand" />{{ __('Onlayn qabul sahifasi') }}</div>
            <x-field name="online_enabled" type="checkbox" :label="__('Yoqilgan')" :value="$cfg('online_enabled')" />
        </div>
        <div class="mt-4 grid md:grid-cols-2 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Sahifa manzili') }}</label>
                <div class="flex items-center rounded-xl border border-slate-200 overflow-hidden"><span class="px-3 text-sm text-slate-500 bg-slate-50 py-2.5 whitespace-nowrap">{{ url('/k') }}/</span><input name="slug" value="{{ old('slug', $clinic->slug) }}" class="flex-1 px-3 py-2.5 text-sm outline-none bg-transparent" required></div>
                @error('slug')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex gap-2">
                <a href="{{ $clinic->bookingUrl() }}" target="_blank" class="btn"><x-icon name="eye" class="w-4 h-4" />{{ __('Ochish') }}</a>
                <button type="button" class="btn" @click="navigator.clipboard.writeText('{{ $clinic->bookingUrl() }}'); copied = 'link'"><x-icon name="check" class="w-4 h-4" /><span x-text="copied === 'link' ? @js(__('Nusxalandi')) : @js(__('Havolani nusxalash'))"></span></button>
            </div>
        </div>
        <div class="mt-5 grid grid-cols-2 md:grid-cols-4 gap-4">
            <x-field name="online_start" type="time" :label="__('Ish boshlanishi')" :value="$cfg('online_start')" required />
            <x-field name="online_end" type="time" :label="__('Ish tugashi')" :value="$cfg('online_end')" required />
            <x-field name="online_days_ahead" type="number" :label="__('Necha kun oldinga')" :value="$cfg('online_days_ahead')" required />
            <x-field name="online_lead_min" type="number" :label="__('Eng yaqin vaqt (daqiqa)')" :value="$cfg('online_lead_min')" required :hint="__('Hozirdan shuncha daqiqadan keyingi vaqtlar ochiq')" />
        </div>
        <div class="mt-4">
            <div class="text-sm font-medium text-slate-700 mb-2">{{ __('Ish kunlari') }}</div>
            <div class="flex flex-wrap gap-2">@foreach($days as $n => $l)<label class="cursor-pointer"><input type="checkbox" name="online_days[]" value="{{ $n }}" class="peer sr-only" @checked(in_array($n, old('online_days', $selDays)))><span class="inline-block px-3.5 py-1.5 rounded-lg border border-slate-200 text-sm peer-checked:bg-brand peer-checked:text-white peer-checked:border-transparent">{{ __($l) }}</span></label>@endforeach</div>
        </div>
        <div class="mt-4 max-w-xs"><x-field name="online_hold_min" type="number" :label="__('To\'lanmagan bron saqlanish vaqti (daqiqa)')" :value="$cfg('online_hold_min')" required /></div>
    </div>

    <div class="card p-6">
        <div class="font-semibold flex items-center gap-2"><x-icon name="file-text" class="w-4 h-4 text-brand" />{{ __('Onlayn qabul oferta shartlari') }}</div>
        <p class="text-sm text-slate-500 mt-1">{{ __("Bemor onlayn yozilishda shu shartlarga rozilik beradi. Bo'sh qoldirsangiz, standart matn ko'rsatiladi.") }}</p>
        <textarea name="booking_offer" rows="8" class="input mt-3 font-mono text-sm" placeholder="{{ __('Standart matn ishlatiladi') }}">{{ old('booking_offer', $clinic->setting('booking_offer')) }}</textarea>
        <a href="{{ route('booking.offer', $clinic->slug) }}" target="_blank" class="link text-sm mt-2 inline-block">{{ __("Ko'rib chiqish") }}</a>
    </div>

    <div class="card p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="font-semibold flex items-center gap-2"><x-icon name="credit-card" class="w-4 h-4 text-brand" />{{ __('Klinikaning Payme kassasi') }}</div>
            <span class="badge ring-1 ring-inset {{ $paymeReady ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : 'bg-amber-50 text-amber-700 ring-amber-600/20' }}">{{ $paymeReady ? __('Ulangan') : __('Ulanmagan') }}</span>
        </div>
        <p class="text-sm text-slate-500 mt-2">{{ __("Onlayn qabul to'lovlari bevosita klinikangizning Payme hisobiga tushadi. Buning uchun Payme Business (business.paycom.uz) da o'z kassangizni oching.") }}</p>
        <div class="mt-4 grid md:grid-cols-2 gap-4">
            <x-field name="payme_merchant_id" :label="__('Merchant ID (Kassa ID)')" :value="$clinic->setting('payme_merchant_id')" />
            <div></div>
            <x-field name="payme_key" type="password" :label="__('Kalit (KEY)')" :hint="$clinic->setting('payme_key') ? __('Saqlangan. O\'zgartirmaslik uchun bo\'sh qoldiring') : null" />
            <x-field name="payme_test_key" type="password" :label="__('Test kalit (TEST_KEY)')" :hint="$clinic->setting('payme_test_key') ? __('Saqlangan. O\'zgartirmaslik uchun bo\'sh qoldiring') : null" />
        </div>
        <div class="mt-3"><x-field name="payme_test" type="checkbox" :label="__('Test rejimi (test.paycom.uz)')" :value="$cfg('payme_test')" /></div>

        <div class="mt-5 rounded-xl bg-slate-50 border border-slate-200 p-4 text-sm space-y-2">
            <div class="font-medium text-slate-800">{{ __('Payme Business kabinetida quyidagilarni kiriting:') }}</div>
            <div class="flex flex-wrap items-center gap-2"><span class="text-slate-500">{{ __('Endpoint URL') }}:</span><code class="px-2 py-1 rounded bg-white border border-slate-200 text-xs break-all">{{ $endpoint }}</code>
                <button type="button" class="btn btn-sm" @click="navigator.clipboard.writeText('{{ $endpoint }}'); copied = 'ep'"><span x-text="copied === 'ep' ? @js(__('Nusxalandi')) : @js(__('Nusxalash'))"></span></button></div>
            <div><span class="text-slate-500">{{ __('Hisob (account) maydoni nomi') }}:</span> <code class="px-2 py-1 rounded bg-white border border-slate-200 text-xs">order_id</code> <span class="text-slate-500">({{ __('turi: raqam') }})</span></div>
            <p class="text-xs text-slate-500">{{ __("Endpoint HTTPS bilan ochiq (internetdan ko'rinadigan) domenda turishi kerak. Mahalliy kompyuterda (127.0.0.1) Payme so'rov yubora olmaydi.") }}</p>
        </div>
    </div>

    <div class="card p-6">
        <div class="font-semibold flex items-center gap-2 mb-4"><x-icon name="tag" class="w-4 h-4 text-brand" />{{ __('Onlayn qabulda ko\'rinadigan xizmatlar') }}</div>
        <div class="grid sm:grid-cols-2 gap-2">
            @foreach($services as $s)
                <label class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 px-3.5 py-2.5 cursor-pointer hover:bg-slate-50">
                    <span class="flex items-center gap-2.5"><input type="checkbox" name="services[]" value="{{ $s->id }}" class="rounded" @checked(old('services') ? in_array($s->id, old('services')) : $s->online_enabled)><span class="text-sm">{{ $s->name }}</span></span>
                    <span class="text-xs text-slate-500">{{ money($s->price) }}</span>
                </label>
            @endforeach
            @if($services->isEmpty())<p class="text-sm text-slate-500">{{ __("Avval Xizmatlar bo'limida xizmat qo'shing.") }}</p>@endif
        </div>
    </div>

    <div class="card p-6">
        <div class="font-semibold flex items-center gap-2 mb-1"><x-icon name="stethoscope" class="w-4 h-4 text-brand" />{{ __("Onlayn qabulda ko'rinadigan shifokorlar") }}</div>
        <p class="text-xs text-slate-500 mb-4">{{ __("Shifokorga xizmat belgilanmasa — u tanlangan barcha xizmatlarda ko'rinadi.") }}</p>
        <div class="space-y-3">
            @foreach($doctors as $d)
                <div class="rounded-xl border border-slate-200 p-4" x-data="{ on: {{ $d->online_enabled ? 'true' : 'false' }} }">
                    <label class="flex items-center gap-2.5 cursor-pointer"><input type="checkbox" name="doctors[]" value="{{ $d->id }}" x-model="on" class="rounded"><span class="font-medium text-sm">{{ $d->name }}</span><span class="text-xs text-slate-500">{{ $d->specialty }}</span></label>
                    <div class="mt-3 flex flex-wrap gap-2" x-show="on" x-cloak>
                        @foreach($services as $s)
                            <label class="cursor-pointer"><input type="checkbox" name="links[{{ $d->id }}][]" value="{{ $s->id }}" class="peer sr-only" @checked(in_array($s->id, $links[$d->id] ?? []))><span class="inline-block px-3 py-1 rounded-lg border border-slate-200 text-xs peer-checked:bg-brand peer-checked:text-white peer-checked:border-transparent">{{ $s->name }}</span></label>
                        @endforeach
                    </div>
                </div>
            @endforeach
            @if($doctors->isEmpty())<p class="text-sm text-slate-500">{{ __("Avval Xodimlar bo'limida shifokor qo'shing.") }}</p>@endif
        </div>
    </div>
    <button class="btn btn-primary"><x-icon name="check" class="w-4 h-4" />{{ __('Saqlash') }}</button>
</form>
@endsection
