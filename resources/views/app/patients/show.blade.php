@extends('layouts.app')
@section('title', $patient->full_name)
@section('content')
@php
    $u = auth()->user();
@endphp
<div class="card p-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold">{{ $patient->full_name }}</h2>
            <div class="text-sm text-slate-500 mt-1">
                {{ $patient->birth_date?->format('d.m.Y') }} @if($patient->age() !== null)({{ $patient->age() }} {{ __('yosh') }})@endif
                · {{ $patient->gender === 'm' ? __('Erkak') : ($patient->gender === 'f' ? __('Ayol') : '') }}
                · {{ phone_fmt($patient->phone) }} @if($patient->pinfl)· {{ __('JSHSHIR') }}: {{ $patient->pinfl }}@endif
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($u->hasRole('clinic_admin', 'receptionist'))<a class="btn btn-sm" href="{{ route('app.patients.edit', $patient) }}">{{ __('Tahrirlash') }}</a>@endif
            @if($u->hasRole('clinic_admin', 'receptionist', 'doctor'))<a class="btn btn-sm" href="{{ route('app.appointments.create', ['patient' => $patient->id]) }}"><x-icon name="plus" class="w-4 h-4" />{{ __('Qabul') }}</a>@endif
            @if($u->hasRole('clinic_admin', 'doctor'))<a class="btn btn-sm btn-primary" href="{{ route('app.visits.create', ['patient' => $patient->id]) }}"><x-icon name="plus" class="w-4 h-4" />{{ __('Tashrif (ko\'rik)') }}</a>@endif
            @if($u->hasRole('clinic_admin', 'receptionist', 'cashier'))<a class="btn btn-sm" href="{{ route('app.invoices.create', ['patient' => $patient->id]) }}"><x-icon name="plus" class="w-4 h-4" />{{ __('Hisob-faktura') }}</a>@endif
            @if($u->hasRole('clinic_admin', 'receptionist', 'doctor'))<a class="btn btn-sm" href="{{ route('app.lab.create', ['patient' => $patient->id]) }}"><x-icon name="plus" class="w-4 h-4" />{{ __('Tahlil') }}</a>@endif
            @if($u->isSuper())
                <form method="POST" action="{{ route('admin.patients.destroy', $patient) }}" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__('Bemorni o\'chirasizmi?')) }})">@csrf @method('DELETE')<button class="btn btn-sm btn-danger">{{ __("O'chirish") }}</button></form>
            @endif
        </div>
    </div>
    <div class="grid sm:grid-cols-2 gap-x-6 gap-y-2 mt-4 text-sm">
        @if($patient->blood_group)<div><span class="text-slate-500">{{ __('Qon guruhi') }}:</span> {{ $patient->blood_group }}</div>@endif
        @if($patient->address)<div><span class="text-slate-500">{{ __('Manzil') }}:</span> {{ $patient->address }}</div>@endif
        @if($patient->allergies)<div class="text-red-700"><span class="font-medium">{{ __('Allergiya') }}:</span> {{ $patient->allergies }}</div>@endif
        @if($patient->chronic_diseases)<div><span class="text-slate-500">{{ __('Surunkali kasalliklar') }}:</span> {{ $patient->chronic_diseases }}</div>@endif
        @if($patient->notes)<div class="sm:col-span-2"><span class="text-slate-500">{{ __('Izoh') }}:</span> {{ $patient->notes }}</div>@endif
    </div>
</div>

@if($balance)
@php
    $bal = $balance['balance'];
    $methodsList = collect(\App\Models\Invoice::methods())->map(fn ($l) => __($l))->all();
@endphp
<div class="card p-5 mt-4" x-data="{ f: null }">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <span class="h-12 w-12 rounded-xl grid place-items-center {{ $bal < 0 ? 'bg-red-50 text-red-600' : ($bal > 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-500') }}"><x-icon :name="$bal < 0 ? 'alert' : 'wallet'" class="w-6 h-6" /></span>
            <div>
                <div class="text-xs text-slate-500">{{ __('Bemorning umumiy balansi') }}</div>
                <div class="text-2xl font-bold {{ $bal < 0 ? 'text-red-600' : ($bal > 0 ? 'text-emerald-600' : 'text-slate-900') }}">
                    @if($bal < 0){{ __('Qarz') }}: {{ money(-$bal) }}@elseif($bal > 0){{ __('Ortiqcha to\'lov (avans)') }}: {{ money($bal) }}@else{{ __('Qarz yo\'q') }} · 0 {{ __("so'm") }}@endif
                </div>
                <div class="text-xs text-slate-500 mt-0.5">{{ __('Hisob-fakturalar qarzi') }}: {{ money($balance['debt']) }} · {{ __('Avans') }}: {{ money($balance['credit']) }}</div>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="btn btn-sm btn-primary" @click="f = f === 'dep' ? null : 'dep'"><x-icon name="plus" class="w-4 h-4" />{{ __('Avans qabul qilish') }}</button>
            @if($balance['credit'] > 0)<button type="button" class="btn btn-sm" @click="f = f === 'ref' ? null : 'ref'">{{ __('Avansni qaytarish') }}</button>@endif
        </div>
    </div>
    <form method="POST" action="{{ route('app.patients.deposit', $patient) }}" x-show="f === 'dep'" x-cloak class="mt-4 grid md:grid-cols-4 gap-2 items-end">@csrf
        <input type="number" name="amount" min="1" class="input" placeholder="{{ __('Summa') }}" value="{{ $bal < 0 ? -$bal : '' }}" required>
        <select name="method" class="input">@foreach($methodsList as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
        <input type="text" name="note" class="input" placeholder="{{ __('Izoh') }}">
        <button class="btn btn-primary">{{ __('Qabul qilish') }}</button>
    </form>
    @if($balance['credit'] > 0)
    <form method="POST" action="{{ route('app.patients.refund', $patient) }}" x-show="f === 'ref'" x-cloak class="mt-4 grid md:grid-cols-4 gap-2 items-end" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__('Avans bemorga qaytarilsinmi?')) }})">@csrf
        <input type="number" name="amount" min="1" max="{{ $balance['credit'] }}" class="input" value="{{ $balance['credit'] }}" required>
        <select name="method" class="input"><option value="cash">{{ __('Naqd') }}</option><option value="card">{{ __('Karta') }}</option><option value="transfer">{{ __("Bank o'tkazmasi") }}</option></select>
        <input type="text" name="note" class="input" placeholder="{{ __('Izoh') }}">
        <button class="btn">{{ __('Qaytarish') }}</button>
    </form>
    @endif
    @if($advances && ($advances['payments']->isNotEmpty() || $advances['used']->isNotEmpty()))
    <div class="mt-4 border-t border-slate-100 pt-3 grid md:grid-cols-2 gap-4 text-sm">
        <div><div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">{{ __('Kelgan avanslar') }}</div>
            @foreach($advances['payments'] as $ap)<div class="flex justify-between py-1"><span class="text-slate-600">{{ $ap->paid_at->format('d.m.Y') }} · {{ __(\App\Models\Invoice::methods()[$ap->method] ?? $ap->method) }}</span><b class="text-emerald-600">+ {{ money($ap->amount) }}</b></div>@endforeach</div>
        <div><div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">{{ __('Sarflangan / qaytarilgan') }}</div>
            @foreach($advances['used'] as $au)<div class="flex justify-between py-1"><span class="text-slate-600">{{ $au->created_at->format('d.m.Y') }} · {{ $au->kind === 'refund' ? __('Qaytarildi') : __('Hisob-faktura yopildi') }}</span><b>− {{ money($au->amount) }}</b></div>@endforeach</div>
    </div>
    @endif
</div>
@endif

<div class="grid lg:grid-cols-2 gap-4 mt-4">
    <div class="card overflow-hidden">
        <div class="px-4 py-3 font-semibold">{{ __('Kasallik tarixi (tashriflar)') }}</div>
        <table class="w-full">@forelse($patient->visits->take(15) as $v)
            <tr><td class="td whitespace-nowrap">{{ $v->visited_at->format('d.m.Y') }}</td><td class="td">{{ $v->diagnosis_text ?: $v->complaint }}<div class="text-xs text-slate-500">{{ $v->doctor?->name }}</div></td>
            <td class="td text-right">@if($u->hasRole('clinic_admin', 'doctor'))<a class="link" href="{{ route('app.visits.show', $v) }}">{{ __("Ko'rish") }}</a>@endif</td></tr>
        @empty<tr><td class="td text-slate-500">{{ __("Tashriflar yo'q") }}</td></tr>@endforelse</table>
    </div>
    <div class="card overflow-hidden">
        <div class="px-4 py-3 font-semibold">{{ __('Qabullar') }}</div>
        <table class="w-full">@forelse($patient->appointments->sortByDesc('starts_at')->take(10) as $a)
            <tr><td class="td whitespace-nowrap">{{ $a->starts_at->format('d.m.Y H:i') }}</td><td class="td">{{ $a->doctor?->name }}</td><td class="td"><x-badge :color="['done' => 'green', 'canceled' => 'red', 'no_show' => 'red', 'in_progress' => 'blue', 'arrived' => 'yellow'][$a->status] ?? 'slate'">{{ $a->statusLabel() }}</x-badge></td></tr>
        @empty<tr><td class="td text-slate-500">—</td></tr>@endforelse</table>
    </div>
    @if($u->hasRole('clinic_admin', 'receptionist', 'cashier'))
    <div class="card overflow-hidden">
        <div class="px-4 py-3 font-semibold">{{ __("To'lovlar va hisob-fakturalar") }}</div>
        <table class="w-full">@forelse($patient->invoices->take(10) as $i)
            <tr><td class="td"><a class="link" href="{{ route('app.invoices.show', $i) }}">{{ $i->number }}</a></td><td class="td">{{ money($i->netTotal()) }}</td>
            <td class="td">{{ $i->remaining() > 0 && $i->status !== 'canceled' ? __('Qarz').': '.money($i->remaining()) : '' }}</td>
            <td class="td"><x-badge :color="['paid' => 'green', 'partial' => 'yellow', 'unpaid' => 'red', 'canceled' => 'slate'][$i->status] ?? 'slate'">{{ $i->statusLabel() }}</x-badge></td></tr>
        @empty<tr><td class="td text-slate-500">—</td></tr>@endforelse</table>
    </div>
    @endif
    <div class="card overflow-hidden">
        <div class="px-4 py-3 font-semibold">{{ __('Tahlillar') }}</div>
        <table class="w-full">@forelse($patient->labOrders->take(10) as $l)
            <tr><td class="td whitespace-nowrap">{{ $l->ordered_at->format('d.m.Y') }}</td><td class="td">{{ $l->test_name }}<div class="text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($l->result_value, 80) }}</div></td>
            <td class="td"><x-badge :color="$l->status === 'done' ? 'green' : 'yellow'">{{ $l->status === 'done' ? __('Tayyor') : __('Kutilmoqda') }}</x-badge></td>
            <td class="td">@if($u->hasRole('clinic_admin', 'receptionist', 'doctor', 'laborant'))<a class="link" href="{{ route('app.lab.edit', $l) }}">{{ __("Ko'rish") }}</a>@endif</td></tr>
        @empty<tr><td class="td text-slate-500">—</td></tr>@endforelse</table>
    </div>
</div>
@if($u->hasRole('clinic_admin', 'receptionist', 'doctor'))
<div class="card overflow-hidden mt-4" x-data="{ open: false, src: '' }">
    <div class="section-title"><x-icon name="eye" class="w-4 h-4 text-brand" />{{ __('Rasmlar') }} <span class="text-xs font-normal text-slate-400">({{ $photos->count() }})</span></div>
    <div class="p-5">
        <form method="POST" action="{{ route('app.photos.store', $patient) }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3 mb-4">
            @csrf
            <div class="flex-1 min-w-[220px]"><label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Rasm(lar) tanlang') }}</label>
                <input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" required class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm"></div>
            <div class="w-56"><label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Izoh (ixtiyoriy)') }}</label><input name="title" class="input" placeholder="{{ __('Rentgen, tahlil...') }}"></div>
            <button class="btn btn-primary"><x-icon name="plus" class="w-4 h-4" />{{ __('Yuklash') }}</button>
            <p class="w-full text-xs text-slate-500">{{ __("JPG, PNG, WebP · 15 MB gacha · Sifat saqlanadi, fayl hajmi avtomatik kamaytiriladi.") }}</p>
        </form>
        @error('photos')<p class="text-xs text-red-600 mb-2">{{ $message }}</p>@enderror
        @if($photos->count())
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
            @foreach($photos as $ph)
                <div class="group relative rounded-xl overflow-hidden border border-slate-200 bg-slate-50 aspect-square">
                    <img src="{{ route('app.photos.show', $ph) }}" loading="lazy" alt="{{ $ph->title }}" class="h-full w-full object-cover cursor-zoom-in" @click="src = '{{ route('app.photos.show', $ph) }}'; open = true">
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-2 text-[11px] text-white">
                        <div class="truncate">{{ $ph->title ?: $ph->created_at->format('d.m.Y') }}</div>
                    </div>
                    @if($u->hasRole('clinic_admin', 'doctor'))
                    <form method="POST" action="{{ route('app.photos.destroy', $ph) }}" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__('Rasm o\'chirilsinmi?')) }})" class="absolute top-1.5 right-1.5 opacity-0 group-hover:opacity-100 transition">@csrf @method('DELETE')
                        <button class="h-7 w-7 grid place-items-center rounded-lg bg-white/90 text-red-600 shadow"><x-icon name="trash" class="w-4 h-4" /></button></form>
                    @endif
                </div>
            @endforeach
        </div>
        @else<div class="text-sm text-slate-500 text-center py-6">{{ __("Hali rasm yuklanmagan") }}</div>@endif
    </div>
    <div x-show="open" x-cloak @click="open = false" @keydown.escape.window="open = false" class="fixed inset-0 z-50 bg-black/80 grid place-items-center p-4">
        <img :src="src" class="max-h-[92vh] max-w-[92vw] rounded-lg shadow-2xl" alt="">
        <button class="absolute top-4 right-4 h-10 w-10 grid place-items-center rounded-full bg-white/90"><x-icon name="x" class="w-5 h-5" /></button>
    </div>
</div>
@endif
@endsection
