@extends('layouts.app')
@section('title', __("Klinika ma'lumotlari"))
@section('content')
<form method="POST" action="{{ route('app.profile.update') }}" enctype="multipart/form-data" class="space-y-6 max-w-4xl">
    @csrf @method('PUT')
    <div class="card p-6">
        <div class="font-semibold mb-4">{{ __('Asosiy') }}</div>
        <div class="grid md:grid-cols-2 gap-4">
            <x-field name="name" :label="__('Klinika nomi')" :value="$clinic->name" required />
            <x-field name="owner_name" :label="__('Rahbar F.I.Sh.')" :value="$clinic->owner_name" required />
            <x-field name="phone" :label="__('Asosiy telefon (SMS xabarlar shu raqamga keladi)')" :value="$clinic->phone" required />
            <x-field name="phone2" :label="__('Qo\'shimcha telefon(lar)')" :value="$clinic->setting('phone2')" />
            <x-field name="email" type="email" :label="__('Email')" :value="$clinic->email" />
            <x-field name="working_hours" :label="__('Ish vaqti')" :value="$clinic->setting('working_hours')" placeholder="Du–Sha 08:00–18:00" />
            <x-field name="address" :label="__('Manzil')" :value="$clinic->address" span="2" />
            <x-field name="map_link" :label="__('Xarita havolasi')" :value="$clinic->setting('map_link')" span="2" />
            <x-field name="description" type="textarea" :label="__('Klinika haqida')" :value="$clinic->setting('description')" span="2" />
        </div>
    </div>
    <div class="card p-6">
        <div class="font-semibold mb-4">{{ __('Logotip') }}</div>
        @if($clinic->logoUrl())<div class="flex items-center gap-4 mb-3"><img src="{{ $clinic->logoUrl() }}" class="h-16" alt=""><x-field name="remove_logo" type="checkbox" :label="__('Logotipni o\'chirish')" /></div>@endif
        <x-field name="logo" type="file" :label="__('Yangi logotip (PNG/JPG, 2 MB gacha)')" attrs='accept="image/*"' />
    </div>
    <div class="card p-6">
        <div class="font-semibold mb-4">{{ __('Ijtimoiy tarmoqlar') }}</div>
        <div class="grid md:grid-cols-3 gap-4">
            <x-field name="website" :label="__('Sayt')" :value="$clinic->setting('website')" />
            <x-field name="telegram" :label="__('Telegram')" :value="$clinic->setting('telegram')" />
            <x-field name="instagram" :label="__('Instagram')" :value="$clinic->setting('instagram')" />
        </div>
    </div>
    <div class="card p-6">
        <div class="font-semibold mb-4">{{ __('Rekvizitlar va hujjatlar') }}</div>
        <div class="grid md:grid-cols-2 gap-4">
            <x-field name="inn" :label="__('STIR (INN)')" :value="$clinic->setting('inn')" />
            <x-field name="license_no" :label="__('Litsenziya raqami')" :value="$clinic->setting('license_no')" />
            <x-field name="bank_details" type="textarea" :label="__('Bank rekvizitlari')" :value="$clinic->setting('bank_details')" span="2" />
            <x-field name="invoice_header" type="textarea" :label="__('Chek/hujjat sarlavhasi (chop etishda ko\'rinadi)')" :value="$clinic->setting('invoice_header')" span="2" />
        </div>
    </div>
    <button class="btn btn-primary">{{ __('Saqlash') }}</button>
</form>
@endsection
