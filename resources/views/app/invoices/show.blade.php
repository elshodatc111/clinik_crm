@extends('layouts.app')
@section('title', __('Hisob-faktura').' '.$invoice->number)
@section('content')
<div class="grid lg:grid-cols-3 gap-4">
    <div class="card p-6 lg:col-span-2">
        <div class="flex flex-wrap justify-between gap-3">
            <div>
                <div class="text-lg font-bold">{{ $invoice->number }} <x-badge :color="['paid' => 'green', 'partial' => 'yellow', 'unpaid' => 'red', 'canceled' => 'slate'][$invoice->status] ?? 'slate'">{{ $invoice->statusLabel() }}</x-badge></div>
                <div class="text-sm text-slate-500">{{ $invoice->created_at->format('d.m.Y H:i') }} · <a class="link" href="{{ route('app.patients.show', $invoice->patient_id) }}">{{ $invoice->patient?->full_name }}</a>@if($invoice->doctor) · {{ $invoice->doctor->name }}@endif</div>
            </div>
            <div class="flex gap-2 print:hidden"><a class="btn btn-sm" target="_blank" href="{{ route('app.invoices.print', $invoice) }}">{{ __('Chop etish') }}</a></div>
        </div>
        <table class="w-full mt-4">
            <thead><tr><th class="th">{{ __('Xizmat') }}</th><th class="th">{{ __('Soni') }}</th><th class="th">{{ __('Narx') }}</th><th class="th text-right">{{ __('Summa') }}</th></tr></thead>
            @foreach($invoice->items as $it)<tr><td class="td">{{ $it->name }}</td><td class="td">{{ $it->qty }}</td><td class="td">{{ money($it->price) }}</td><td class="td text-right">{{ money($it->qty * $it->price) }}</td></tr>@endforeach
            <tr><td class="td" colspan="3" align="right">{{ __('Jami') }}</td><td class="td text-right">{{ money($invoice->total) }}</td></tr>
            @if($invoice->discount)<tr><td class="td" colspan="3" align="right">{{ __('Chegirma') }}</td><td class="td text-right">−{{ money($invoice->discount) }}</td></tr>@endif
            <tr><td class="td font-semibold" colspan="3" align="right">{{ __("To'lanishi kerak") }}</td><td class="td text-right font-semibold">{{ money($invoice->netTotal()) }}</td></tr>
            <tr><td class="td" colspan="3" align="right">{{ __("To'langan") }}</td><td class="td text-right text-green-700">{{ money($invoice->paid) }}</td></tr>
            <tr><td class="td font-semibold" colspan="3" align="right">{{ __('Qoldiq') }}</td><td class="td text-right font-semibold {{ $invoice->remaining() > 0 && $invoice->status !== 'canceled' ? 'text-red-600' : '' }}">{{ money($invoice->remaining()) }}</td></tr>
        </table>
        @if($invoice->notes)<p class="text-sm text-slate-600 mt-3">{{ $invoice->notes }}</p>@endif
    </div>

    <div class="space-y-4">
        @if($invoice->status !== 'canceled' && $invoice->remaining() > 0)
        <form method="POST" action="{{ route('app.invoices.pay', $invoice) }}" class="card p-5 space-y-3">@csrf
            <div class="font-semibold">{{ __("To'lov qabul qilish") }}</div>
            <x-field name="amount" type="number" :label="__('Summa')" :value="$invoice->remaining()" required />
            <x-field name="method" type="select" :label="__('To\'lov turi')" :options="collect(\App\Models\Invoice::methods())->map(fn ($l) => __($l))->all()" />
            <x-field name="note" :label="__('Izoh')" />
            <x-field name="overpay" type="checkbox" :label="__('Ortiqcha summani avans sifatida qoldirish')" />
            <button class="btn btn-primary w-full">{{ __('Qabul qilish') }}</button>
        </form>
        @php
            $pb = \App\Services\PatientBalance::forPatient($invoice->patient_id);
            $canUse = min($pb['credit'], $invoice->remaining());
        @endphp
        @if($canUse > 0)
        <form method="POST" action="{{ route('app.invoices.apply-credit', $invoice) }}" class="card p-5 space-y-3 border-emerald-200">@csrf
            <div class="font-semibold flex items-center gap-2 text-emerald-700"><x-icon name="wallet" class="w-4 h-4" />{{ __('Bemorning avansi bor') }}: {{ money($pb['credit']) }}</div>
            <x-field name="amount" type="number" :label="__('Avansdan yopiladigan summa')" :value="$canUse" attrs='max="{{ $canUse }}" min="1"' />
            <button class="btn w-full">{{ __('Avansdan yopish') }}</button>
        </form>
        @endif
        @endif
        <div class="card overflow-hidden">
            <div class="px-4 py-3 font-semibold">{{ __("To'lovlar tarixi") }}</div>
            <table class="w-full">@forelse($invoice->payments as $p)
                <tr><td class="td">{{ $p->paid_at->format('d.m.Y H:i') }}<div class="text-xs text-slate-500">{{ $p->cashier?->name }}</div></td><td class="td">{{ __(\App\Models\Invoice::methods()[$p->method] ?? $p->method) }}</td><td class="td text-right font-medium">{{ money($p->amount) }}</td></tr>
            @empty<tr><td class="td text-slate-500">—</td></tr>@endforelse</table>
        </div>
        @if(auth()->user()->hasRole('clinic_admin') && $invoice->paid == 0 && $invoice->status !== 'canceled')
            <form method="POST" action="{{ route('app.invoices.cancel', $invoice) }}" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__('Bekor qilasizmi?')) }})">@csrf<button class="btn btn-danger btn-sm">{{ __('Hisob-fakturani bekor qilish') }}</button></form>
        @endif
    </div>
</div>
@endsection
