@extends('layouts.app')
@section('title', __("To'lovlar va balans harakatlari"))
@section('content')
<form method="GET" class="flex gap-2 mb-4">
    <select name="type" class="input max-w-xs"><option value="">{{ __('Barcha turlar') }}</option>@foreach(['topup' => "Balans to'ldirish", 'charge' => 'Oylik yechim', 'setup_fee' => "Ulanish to'lovi", 'manual' => "Qo'lda", 'refund' => 'Qaytarish'] as $k => $l)<option value="{{ $k }}" @selected(request('type') === $k)>{{ __($l) }}</option>@endforeach</select>
    <button class="btn">{{ __('Filtr') }}</button>
</form>
<div class="card overflow-x-auto">
    <table class="w-full"><thead><tr><th class="th">{{ __('Sana') }}</th><th class="th">{{ __('Klinika') }}</th><th class="th">{{ __('Turi') }}</th><th class="th">{{ __('Summa') }}</th><th class="th">{{ __('Qoldiq') }}</th><th class="th">{{ __('Izoh') }}</th></tr></thead>
    @foreach($items as $t)
        <tr><td class="td">{{ $t->created_at?->format('d.m.Y H:i') }}</td><td class="td"><a class="link" href="{{ route('admin.clinics.show', $t->clinic_id) }}">{{ $t->clinic?->name }}</a></td><td class="td">{{ $t->type }}</td>
        <td class="td {{ $t->amount < 0 ? 'text-red-600' : 'text-green-700' }}">{{ ($t->amount > 0 ? '+' : '').number_format($t->amount, 0, '.', ' ') }}</td><td class="td">{{ number_format($t->balance_after, 0, '.', ' ') }}</td><td class="td text-slate-500">{{ $t->description }}</td></tr>
    @endforeach</table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
