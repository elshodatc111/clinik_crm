@extends('layouts.app')
@section('title', __('Ommaviy oferta'))
@section('content')
<div class="grid lg:grid-cols-2 gap-4">
    <div class="card p-5">
        <div class="font-semibold mb-3">{{ __('Yangi versiya yaratish') }}</div>
        <form method="POST" action="{{ route('admin.offers.store') }}" class="space-y-3">@csrf
            <x-field name="title" :label="__('Sarlavha')" :value="$offers->first()->title ?? ''" required />
            <p class="text-xs text-slate-500">{{ __("O'zgaruvchilar:") }} <code>@{{APP}} @{{COMPANY}} @{{DATE}} @{{ADDRESS}} @{{PHONE}} @{{EMAIL}} @{{SITE}}</code> — {{ __("Sozlamalardagi ma'lumotlar bilan almashtiriladi. Yangi versiya faollashganda barcha xodimlar qayta rozilik berishi shart.") }}</p>
            <x-field name="body" type="textarea" :label="__('Oferta matni (o\'zbekcha)')" :value="$offers->first()->body ?? ''" rows="14" required />
            <x-field name="body_ru" type="textarea" :label="__('Oferta matni (ruscha)')" :value="$offers->first()->body_ru ?? ''" rows="14" />
            <button class="btn btn-primary">{{ __('Yangi versiya sifatida faollashtirish') }}</button>
        </form>
    </div>
    <div class="space-y-4">
        <div class="card overflow-hidden">
            <div class="px-4 py-3 font-semibold">{{ __('Versiyalar') }}</div>
            <table class="w-full">@foreach($offers as $o)
                <tr><td class="td">v{{ $o->version }}</td><td class="td">{{ $o->title }}</td><td class="td">{{ $o->created_at->format('d.m.Y') }}</td>
                <td class="td">@if($o->is_active)<x-badge color="green">{{ __('Faol') }}</x-badge>@else<form method="POST" action="{{ route('admin.offers.activate', $o) }}">@csrf<button class="btn btn-sm">{{ __('Faollashtirish') }}</button></form>@endif</td></tr>
            @endforeach</table>
        </div>
        <div class="card overflow-hidden">
            <div class="px-4 py-3 font-semibold">{{ __('Oxirgi rozilik yozuvlari') }} <span class="text-xs font-normal text-slate-500">· {{ __('Kirishda tasdiqlagan xodimlar') }}: {{ $staffAccepted }}</span></div>
            <table class="w-full text-xs">@foreach($acceptances as $a)
                <tr><td class="td">{{ $a->accepted_at->format('d.m.Y H:i') }}</td><td class="td">{{ $a->clinic_name }}<div class="text-slate-500">{{ $a->user_name }} · {{ phone_fmt($a->phone) }}</div></td><td class="td">v{{ $a->offer_version }}</td><td class="td">{{ $a->ip_address }}</td></tr>
            @endforeach</table>
        </div>
    </div>
</div>
<div class="card p-5 mt-4">
    <div class="font-semibold mb-1">{{ __('Maxfiylik siyosati') }}</div>
    <p class="text-xs text-slate-500 mb-3">{{ __("Bo'sh qoldirilsa, standart matn ko'rsatiladi (/privacy).") }}</p>
    <form method="POST" action="{{ route('admin.offers.privacy') }}" class="grid lg:grid-cols-2 gap-3">@csrf @method('PUT')
        <x-field name="privacy_uz" type="textarea" :label="__('O\'zbekcha')" :value="\App\Models\Setting::get('privacy_uz', '')" rows="8" />
        <x-field name="privacy_ru" type="textarea" :label="__('Ruscha')" :value="\App\Models\Setting::get('privacy_ru', '')" rows="8" />
        <div><button class="btn btn-primary">{{ __('Saqlash') }}</button> <a href="{{ route('privacy') }}" target="_blank" class="link text-sm ml-2">{{ __("Ko'rish") }}</a></div>
    </form>
</div>
@endsection
