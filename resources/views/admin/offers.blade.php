@extends('layouts.app')
@section('title', __('Ommaviy oferta'))
@section('content')
<div class="grid lg:grid-cols-2 gap-4">
    <div class="card p-5">
        <div class="font-semibold mb-3">{{ __('Yangi versiya yaratish') }}</div>
        <form method="POST" action="{{ route('admin.offers.store') }}" class="space-y-3">@csrf
            <x-field name="title" :label="__('Sarlavha')" :value="$offers->first()->title ?? ''" required />
            <x-field name="body" type="textarea" :label="__('Oferta matni')" :value="$offers->first()->body ?? ''" rows="18" required />
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
            <div class="px-4 py-3 font-semibold">{{ __('Oxirgi rozilik yozuvlari') }}</div>
            <table class="w-full text-xs">@foreach($acceptances as $a)
                <tr><td class="td">{{ $a->accepted_at->format('d.m.Y H:i') }}</td><td class="td">{{ $a->clinic_name }}<div class="text-slate-500">{{ phone_fmt($a->phone) }}</div></td><td class="td">v{{ $a->offer_version }}</td><td class="td">{{ $a->ip_address }}</td></tr>
            @endforeach</table>
        </div>
    </div>
</div>
@endsection
