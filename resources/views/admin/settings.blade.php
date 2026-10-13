@extends('layouts.app')
@section('title', __('Sozlamalar'))
@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-6 max-w-4xl">
    @csrf @method('PUT')
    <div class="card p-6">
        <div class="font-semibold mb-4">{{ __('Brend') }}</div>
        <div class="grid md:grid-cols-2 gap-4">
            <x-field name="app_name" :label="__('Dastur nomi')" :value="$s['app_name'] ?? 'Klinika CRM'" required />
            <x-field name="primary_color" type="color" :label="__('Asosiy rang')" :value="$s['primary_color'] ?? '#1d4ed8'" required />
            <div>
                <x-field name="logo" type="file" :label="__('Logotip')" attrs='accept="image/*"' />
                @if(! empty($s['logo_path']))
                    <div class="mt-2 flex items-center gap-3"><img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($s['logo_path']) }}" class="h-12" alt="">
                    <x-field name="remove_logo" type="checkbox" :label="__('Logotipni o\'chirish')" /></div>
                @endif
            </div>
            <x-field name="support_phone" :label="__('Aloqa telefoni')" :value="$s['support_phone'] ?? ''" />
            <x-field name="support_email" type="email" :label="__('Aloqa emaili')" :value="$s['support_email'] ?? ''" />
        </div>
    </div>

    <div class="card p-6">
        <div class="font-semibold mb-4">{{ __('Obuna sozlamalari') }}</div>
        <div class="grid md:grid-cols-3 gap-4">
            <x-field name="setup_fee" type="number" :label="__('Ulanish to\'lovi (so\'m)')" :value="$s['setup_fee'] ?? 1000000" required />
            <x-field name="demo_days" type="number" :label="__('Demo muddati (kun)')" :value="$s['demo_days'] ?? 14" required />
            <x-field name="demo_grace_days" type="number" :label="__('Demo tugagach o\'chirishgacha qo\'shimcha kun')" :value="$s['demo_grace_days'] ?? 0" required />
        </div>
    </div>

    <div class="card p-6">
        <div class="font-semibold mb-1">{{ __('Payme (Merchant API)') }}</div>
        <p class="text-xs text-slate-500 mb-4">{{ __('Payme kabinetida "Endpoint URL" sifatida quyidagini kiriting:') }} <code class="bg-slate-100 px-1 rounded">{{ url('/payme') }}</code></p>
        <div class="grid md:grid-cols-2 gap-4">
            <x-field name="payme_merchant_id" :label="__('Merchant ID')" :value="$s['payme_merchant_id'] ?? ''" />
            <x-field name="payme_key" type="password" :label="__('Kalit (KEY)')" :hint="! empty($s['payme_key']) ? __('Saqlangan. O\'zgartirish uchun yangisini kiriting.') : null" />
            <x-field name="payme_test_key" type="password" :label="__('Test kalit (TEST_KEY)')" :hint="! empty($s['payme_test_key']) ? __('Saqlangan. O\'zgartirish uchun yangisini kiriting.') : null" />
            <x-field name="payme_test_mode" type="checkbox" :label="__('Test rejimi (test.paycom.uz)')" :value="! empty($s['payme_test_mode'])" />
        </div>
    </div>

    <div class="card p-6">
        <div class="font-semibold mb-4">{{ __('Eskiz.uz (SMS)') }}</div>
        <div class="grid md:grid-cols-3 gap-4">
            <x-field name="eskiz_email" :label="__('Eskiz email')" :value="$s['eskiz_email'] ?? ''" />
            <x-field name="eskiz_password" type="password" :label="__('Eskiz parol')" :hint="! empty($s['eskiz_password']) ? __('Saqlangan. O\'zgartirish uchun yangisini kiriting.') : null" />
            <x-field name="eskiz_from" :label="__('Yuboruvchi (from)')" :value="$s['eskiz_from'] ?? '4546'" />
        </div>
    </div>
    <button class="btn btn-primary">{{ __('Saqlash') }}</button>
</form>
@endsection
