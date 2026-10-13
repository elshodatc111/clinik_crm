@extends('layouts.app')
@section('title', __('Tariflar'))
@section('content')
<p class="text-sm text-slate-600 mb-4">{{ __("Tarif klinikadagi bemorlar soniga qarab avtomatik aniqlanadi. Narxlarni bu yerda o'zgartirishingiz mumkin; yangi narx keyingi oylik to'lovdan boshlab amal qiladi.") }}</p>
<div class="card overflow-x-auto">
    <table class="w-full">
        <thead><tr><th class="th">{{ __('Nomi') }}</th><th class="th">{{ __('Bemorlar (dan)') }}</th><th class="th">{{ __('Bemorlar (gacha)') }}</th><th class="th">{{ __("Oylik narx (so'm)") }}</th><th class="th">{{ __('Tartib') }}</th><th class="th">{{ __('Faol') }}</th><th class="th"></th></tr></thead>
        <tbody>
        @foreach($plans as $p)
            <form method="POST" action="{{ route('admin.plans.update', $p) }}" id="pf{{ $p->id }}">@csrf @method('PUT')</form>
            <tr>
                <td class="td"><input form="pf{{ $p->id }}" name="name" value="{{ $p->name }}" class="input w-28"></td>
                <td class="td"><input form="pf{{ $p->id }}" type="number" name="min_patients" value="{{ $p->min_patients }}" class="input w-28"></td>
                <td class="td"><input form="pf{{ $p->id }}" type="number" name="max_patients" value="{{ $p->max_patients }}" class="input w-28" placeholder="∞"></td>
                <td class="td"><input form="pf{{ $p->id }}" type="number" name="monthly_price" value="{{ $p->monthly_price }}" class="input w-36"></td>
                <td class="td"><input form="pf{{ $p->id }}" type="number" name="sort" value="{{ $p->sort }}" class="input w-20"></td>
                <td class="td"><input form="pf{{ $p->id }}" type="checkbox" name="is_active" value="1" @checked($p->is_active)></td>
                <td class="td"><button form="pf{{ $p->id }}" class="btn btn-sm btn-primary">{{ __('Saqlash') }}</button></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<form method="POST" action="{{ route('admin.plans.store') }}" class="card p-5 mt-6 max-w-3xl">@csrf
    <div class="font-semibold mb-3">{{ __("Yangi tarif qo'shish") }}</div>
    <div class="grid md:grid-cols-4 gap-3">
        <x-field name="name" :label="__('Nomi')" required />
        <x-field name="min_patients" type="number" :label="__('Bemorlar (dan)')" required />
        <x-field name="max_patients" type="number" :label="__('Bemorlar (gacha)')" :hint="__('Bo\'sh = cheksiz')" />
        <x-field name="monthly_price" type="number" :label="__('Oylik narx')" required />
    </div>
    <button class="btn btn-primary mt-4">{{ __("Qo'shish") }}</button>
</form>
@endsection
