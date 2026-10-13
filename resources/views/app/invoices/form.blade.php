@extends('layouts.app')
@section('title', __('Yangi hisob-faktura'))
@section('content')
@php
    $initial = old('items', $rows);
    $initial = $initial ? array_values($initial) : [['service_id' => '', 'name' => '', 'qty' => 1, 'price' => 0]];
@endphp
<script>
function invoiceForm(rows, services, discount) {
    return {
        rows: rows, services: services, discount: discount || 0,
        add() { this.rows.push({service_id: '', name: '', qty: 1, price: 0}); },
        remove(i) { this.rows.splice(i, 1); if (!this.rows.length) this.add(); },
        pick(i) { const s = this.services.find(x => x.id == this.rows[i].service_id); if (s) { this.rows[i].name = s.name; this.rows[i].price = s.price; } },
        get total() { return this.rows.reduce((a, r) => a + (+r.qty || 0) * (+r.price || 0), 0); },
        get net() { return Math.max(0, this.total - (+this.discount || 0)); },
        fmt(n) { return new Intl.NumberFormat('ru-RU').format(n); }
    };
}
</script>
<form method="POST" action="{{ route('app.invoices.store') }}" class="card p-6 max-w-5xl" x-data='invoiceForm(@json($initial), @json($services), {{ (int) old("discount", 0) }})'>
    @csrf
    @if($visit)<input type="hidden" name="visit_id" value="{{ $visit->id }}">@endif
    <div class="mb-4">
        <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Bemor') }} <span class="text-red-500">*</span></label>
        @include('partials.patient-picker', ['patient' => $patient])
    </div>
    <div class="font-semibold mb-2">{{ __('Xizmatlar') }}</div>
    <template x-for="(row, i) in rows" :key="i">
        <div class="grid grid-cols-12 gap-2 mb-2 items-center">
            <select class="input col-span-12 md:col-span-3" :name="'items[' + i + '][service_id]'" x-model="row.service_id" @change="pick(i)">
                <option value="">{{ __('— Xizmat tanlang —') }}</option>
                <template x-for="s in services" :key="s.id"><option :value="s.id" x-text="s.name" :selected="s.id == row.service_id"></option></template>
            </select>
            <input class="input col-span-12 md:col-span-4" :name="'items[' + i + '][name]'" x-model="row.name" placeholder="{{ __('Nomi') }}">
            <input class="input col-span-4 md:col-span-1" type="number" min="1" :name="'items[' + i + '][qty]'" x-model="row.qty">
            <input class="input col-span-5 md:col-span-3" type="number" min="0" :name="'items[' + i + '][price]'" x-model="row.price">
            <button type="button" class="btn btn-sm col-span-3 md:col-span-1" @click="remove(i)">✕</button>
        </div>
    </template>
    <button type="button" class="btn btn-sm" @click="add()"><x-icon name="plus" class="w-4 h-4" />{{ __("Qator qo'shish") }}</button>
    <div class="grid md:grid-cols-2 gap-4 mt-6">
        <x-field name="notes" type="textarea" :label="__('Izoh')" rows="2" />
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ __("Chegirma (so'm)") }}</label>
            <input type="number" name="discount" min="0" x-model="discount" class="input">
            @error('discount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            <div class="mt-3 text-sm text-slate-600">{{ __('Jami') }}: <b x-text="fmt(total)"></b> · {{ __("To'lanadi") }}: <b class="text-lg" x-text="fmt(net)"></b> {{ __("so'm") }}</div>
        </div>
    </div>
    <div class="mt-6 flex gap-2"><button class="btn btn-primary">{{ __('Yaratish') }}</button><a class="btn" href="{{ route('app.invoices.index') }}">{{ __('Bekor qilish') }}</a></div>
</form>
@endsection
