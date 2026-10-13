@extends('layouts.app')
@section('title', __('SMS sozlamalari'))
@section('content')
<div class="max-w-3xl space-y-5">
    <div class="card p-5 flex items-start gap-4 {{ $connected ? '' : 'border-amber-300' }}">
        <span class="h-11 w-11 rounded-xl grid place-items-center {{ $connected ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' }}"><x-icon :name="$connected ? 'check' : 'alert'" class="w-5 h-5" /></span>
        <div class="text-sm">
            <div class="font-semibold text-slate-900">{{ $connected ? __('Eskiz akkauntingiz ulangan') : __('Eskiz akkaunti ulanmagan') }}</div>
            <p class="text-slate-500 mt-1">{{ __("Bemorlarga SMS (eslatma, tahlil natijasi) faqat klinikangizning o'z Eskiz akkaunti orqali yuboriladi va xarajat sizning Eskiz balansingizdan yechiladi. Akkauntni my.eskiz.uz saytida oching.") }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('app.sms.settings.update') }}" class="card p-6 space-y-5">
        @csrf @method('PUT')
        <div class="font-semibold flex items-center gap-2"><x-icon name="settings" class="w-4 h-4 text-brand" />{{ __('Eskiz akkaunti') }}</div>
        <div class="grid md:grid-cols-2 gap-4">
            <x-field name="eskiz_email" type="email" :label="__('Eskiz email (login)')" :value="$clinic->setting('eskiz_email')" />
            <x-field name="eskiz_password" type="password" :label="__('Eskiz parol')" :hint="$connected ? __('O\'zgartirmaslik uchun bo\'sh qoldiring') : null" />
            <x-field name="eskiz_from" :label="__('Jo\'natuvchi nomi (from)')" :value="$clinic->setting('eskiz_from', '4546')" :hint="__('Eskiz tasdiqlagan nom. Standart: 4546')" />
        </div>

        <div class="font-semibold flex items-center gap-2 pt-2 border-t border-slate-100"><x-icon name="message" class="w-4 h-4 text-brand" />{{ __('Avtomatik xabarlar') }}</div>
        <div class="space-y-2">
            <x-field name="sms_remind_24h" type="checkbox" :label="__('Qabuldan 1 kun oldin eslatma')" :value="$clinic->setting('sms_remind_24h', true)" />
            <x-field name="sms_remind_2h" type="checkbox" :label="__('Qabuldan 2 soat oldin eslatma')" :value="$clinic->setting('sms_remind_2h', true)" />
            <x-field name="sms_lab_ready" type="checkbox" :label="__('Tahlil natijasi tayyor bo\'lganda xabar')" :value="$clinic->setting('sms_lab_ready', true)" />
        </div>
        <x-field name="sms_tpl_reminder" type="textarea" rows="3" :label="__('Eslatma matni')" :value="$tpl" :hint="__('Almashtiriladigan so\'zlar: :name (bemor ismi), :date (sana va vaqt), :clinic, :doctor, :address')" />
        <button class="btn btn-primary"><x-icon name="check" class="w-4 h-4" />{{ __('Saqlash') }}</button>
    </form>

    @if($connected)
    <form method="POST" action="{{ route('app.sms.settings.test') }}" class="card p-6 flex flex-wrap items-end gap-3">
        @csrf
        <div class="flex-1 min-w-[220px]"><x-field name="test_phone" :label="__('Sinov SMS yuborish (telefon)')" placeholder="+998901234567" /></div>
        <button class="btn"><x-icon name="message" class="w-4 h-4" />{{ __('Yuborish') }}</button>
    </form>
    @endif
</div>
@endsection
