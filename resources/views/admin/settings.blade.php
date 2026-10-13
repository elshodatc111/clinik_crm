@extends('layouts.app')
@section('title', __('Sozlamalar'))
@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-6 max-w-4xl">
    @csrf @method('PUT')
    <div class="card p-6">
        <div class="font-semibold mb-4">{{ __('Brend') }}</div>
        <div class="grid md:grid-cols-2 gap-4">
            <x-field name="app_name" :label="__('Dastur nomi')" :value="$s['app_name'] ?? 'Klinika CRM'" required />
            <x-field name="company_name" :label="__('Kompaniya (Ijrochi) rasmiy nomi — oferta uchun')" :value="$s['company_name'] ?? ''" :hint="__('Masalan: «NOM» MChJ, STIR ... Oferta va maxfiylik siyosatida ko\'rsatiladi.')" span="2" />
            <x-field name="primary_color" type="color" :label="__('Asosiy rang')" :value="$s['primary_color'] ?? '#1d4ed8'" required />
            <div>
                <x-field name="logo" type="file" :label="__('Logotip')" attrs='accept="image/*"' />
                @if(! empty($s['logo_path']))
                    <div class="mt-2 flex items-center gap-3"><img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($s['logo_path']) }}" class="h-12" alt="">
                    <x-field name="remove_logo" type="checkbox" :label="__('Logotipni o\'chirish')" /></div>
                @endif
            </div>
        </div>
    </div>

    <div class="card p-6">
        <div class="flex items-center gap-2 font-semibold mb-1"><x-icon name="phone" class="w-4 h-4" />{{ __("Aloqa ma'lumotlari") }}</div>
        <p class="text-xs text-slate-500 mb-4">{{ __("Bosh sahifadagi «Bog'lanish» bo'limi va sahifa pastida shu ma'lumotlar ko'rinadi. Bo'sh qoldirilgan maydon ko'rsatilmaydi.") }}</p>
        <div class="grid md:grid-cols-2 gap-4">
            <x-field name="support_phone" :label="__('Telefon')" :value="$s['support_phone'] ?? ''" placeholder="+998 90 123 45 67" />
            <x-field name="support_phone2" :label="__('Qo\'shimcha telefon')" :value="$s['support_phone2'] ?? ''" />
            <x-field name="support_email" type="email" :label="__('Email')" :value="$s['support_email'] ?? ''" />
            <x-field name="contact_hours" :label="__('Ish vaqti')" :value="$s['contact_hours'] ?? ''" placeholder="Du–Sha 09:00–18:00" />
            <x-field name="contact_address" :label="__('Manzil')" :value="$s['contact_address'] ?? ''" span="2" />
            <x-field name="contact_telegram" :label="__('Telegram (@nom yoki havola)')" :value="$s['contact_telegram'] ?? ''" placeholder="@klinika_crm" />
            <x-field name="contact_instagram" :label="__('Instagram (@nom yoki havola)')" :value="$s['contact_instagram'] ?? ''" />
            <x-field name="contact_map_url" :label="__('Xarita (Google Maps embed havolasi, https://...)')" :value="$s['contact_map_url'] ?? ''" span="2" :hint="__('Google Maps → Ulashish → Xaritani joylashtirish → iframe ichidagi src manzilini qo\'ying.')" />
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
