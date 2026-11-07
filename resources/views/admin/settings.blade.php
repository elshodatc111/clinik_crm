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
        <div class="font-semibold mb-1">{{ __("Kartaga to'lov (qo'lda tasdiqlanadigan)") }}</div>
        <p class="text-xs text-slate-500 mb-4">{{ __("Klinikalar obuna uchun shu kartaga pul o'tkazib, chekni yuboradi. Siz chekni tekshirib tasdiqlaysiz yoki sabab bilan bekor qilasiz. Payme ulangan bo'lsa ham ikkala usul ishlaydi.") }}</p>
        <div class="grid md:grid-cols-2 gap-4">
            <x-field name="card_number" :label="__('Karta raqami (16 xonali)')" :value="\App\Services\ManualPaymentService::cardFormatted()" placeholder="8600 0000 0000 0000" attrs='inputmode="numeric" autocomplete="off"' />
            <x-field name="card_holder" :label="__('Karta egasi (F.I.Sh.)')" :value="$s['card_holder'] ?? ''" />
            <x-field name="card_bank" :label="__('Bank / karta turi')" :value="$s['card_bank'] ?? ''" placeholder="Uzcard / Humo" />
            <x-field name="card_note" :label="__('Klinikaga ko\'rsatma (ixtiyoriy)')" :value="$s['card_note'] ?? ''" :hint="__('Masalan: To\'lov izohiga klinika nomini yozing.')" />
            <x-field name="card_enabled" type="checkbox" :label="__('Kartaga to\'lovni yoqish')" :value="($s['card_enabled'] ?? '1') !== '0'" />
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

    <div class="card p-6">
        <div class="font-semibold mb-1">{{ __('SMS Gateway (USB modem)') }}</div>
        <p class="text-xs text-slate-500 mb-4">{{ __("Platforma xabarlari (ro'yxatdan o'tish kodi zaxirasi, obuna xabarlari) avval modem orqali, ishlamasa Eskiz orqali yuboriladi. Klinikaning bemorlarga SMS'lari modemga tegmaydi — ular klinikaning o'z Eskiz akkaunti orqali ketadi.") }}</p>
        <div class="grid md:grid-cols-2 gap-4">
            <x-field name="sms_gateway_url" :label="__('Gateway manzili')" :value="$s['sms_gateway_url'] ?? 'https://sms.elshod.date'" placeholder="https://sms.elshod.date" />
            <x-field name="sms_gateway_key" type="password" :label="__('Gateway API kaliti')" :hint="! empty($s['sms_gateway_key']) ? __('Saqlangan. O\'zgartirish uchun yangisini kiriting.') : null" attrs='autocomplete="new-password"' />
            <x-field name="sms_gateway_on" type="checkbox" :label="__('Modem orqali yuborishni yoqish')" :value="($s['sms_gateway_on'] ?? '1') !== '0'" />
            @if(! empty($s['sms_gateway_key']))<x-field name="forget_gateway_key" type="checkbox" :label="__('Saqlangan kalitni o\'chirish')" :value="false" />@endif
        </div>
    </div>
    <button class="btn btn-primary">{{ __('Saqlash') }}</button>
</form>

@php($gwOn = \App\Services\GatewaySms::enabled())
<div class="max-w-4xl mt-6 space-y-6">
    <div class="card p-6">
        <div class="flex items-center justify-between gap-3 mb-3">
            <div class="font-semibold">{{ __('Modemni tekshirish') }}</div>
            <span class="badge {{ $gwOn ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $gwOn ? __('Asosiy kanal (Eskiz — zaxira)') : __("O'chiq (faqat Eskiz)") }}</span>
        </div>
        <form method="POST" action="{{ route('admin.settings.gateway-check') }}">@csrf <button class="btn btn-sm">{{ __('Gateway ulanishini tekshirish') }}</button></form>
    </div>

    @php($tgOk = \App\Services\TelegramOtp::available())
    <div class="card p-6" id="telegram">
        <div class="flex items-center justify-between gap-3 mb-1">
            <div class="font-semibold">{{ __('Telegram bot (telefonni tasdiqlash)') }}</div>
            @if(! empty($s['tg_bot_username']) && ($s['tg_bot_status'] ?? '') === 'ok')
                <span class="badge bg-emerald-50 text-emerald-700">&#64;{{ $s['tg_bot_username'] }}</span>
            @else
                <span class="badge bg-amber-50 text-amber-700">{{ __('Ulanmagan') }}</span>
            @endif
        </div>
        <p class="text-xs text-slate-500 mb-4">{{ __("Ro'yxatdan o'tish va parolni tiklashda kod shu bot orqali Telegramga yuboriladi. Alohida bot yarating: @BotFather → /newbot → token. Bot ulanganda webhook avtomatik o'rnatiladi (sayt HTTPS bo'lishi shart).") }}</p>
        <form method="POST" action="{{ route('admin.settings.telegram') }}" class="grid md:grid-cols-[1fr_auto] gap-3 items-end">
            @csrf <input type="hidden" name="action" value="connect">
            <x-field name="tg_bot_token" type="password" :label="__('Bot tokeni')" :hint="! empty($s['tg_bot_token']) ? __('Saqlangan. O\'zgartirish uchun yangisini kiriting.') : null" attrs='autocomplete="new-password"' />
            <button class="btn btn-primary">{{ __('Ulash va webhookni o\'rnatish') }}</button>
        </form>
        @if(! empty($s['tg_bot_token']))
        <div class="flex flex-wrap items-center gap-3 mt-4">
            <form method="POST" action="{{ route('admin.settings.telegram') }}" class="flex items-center gap-2">
                @csrf <input type="hidden" name="action" value="toggle">
                <x-field name="tg_otp_enabled" type="checkbox" :label="__('Telegram orqali tasdiqlashni yoqish')" :value="($s['tg_otp_enabled'] ?? '1') !== '0'" />
                <button class="btn btn-sm">{{ __('Saqlash') }}</button>
            </form>
            <form method="POST" action="{{ route('admin.settings.telegram') }}">@csrf <input type="hidden" name="action" value="check"><button class="btn btn-sm">{{ __('Webhookni tekshirish') }}</button></form>
            <form method="POST" action="{{ route('admin.settings.telegram') }}" onsubmit="return confirm('{{ __('Botni uzamizmi?') }}')">@csrf <input type="hidden" name="action" value="disconnect"><button class="btn btn-sm btn-danger">{{ __('Uzish') }}</button></form>
        </div>
        @endif
    </div>
</div>
@endsection
