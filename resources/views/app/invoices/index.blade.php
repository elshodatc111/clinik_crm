@extends('layouts.app')
@section('title', __('Kassa va hisob-fakturalar'))
@section('content')
<div class="flex flex-wrap items-end justify-between gap-3 mb-4">
    <form method="GET" class="flex flex-wrap gap-2 items-end">
        <input name="q" value="{{ request('q') }}" class="input w-56" placeholder="{{ __('Raqam yoki bemor') }}">
        <select name="status" class="input"><option value="">{{ __('Barcha holatlar') }}</option>@foreach(\App\Models\Invoice::statuses() as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ __($l) }}</option>@endforeach</select>
        <input type="date" name="from" value="{{ request('from') }}" class="input"><input type="date" name="to" value="{{ request('to') }}" class="input">
        <button class="btn">{{ __('Filtr') }}</button>
    </form>
    <a href="{{ route('app.invoices.create') }}" class="btn btn-primary"><x-icon name="plus" class="w-4 h-4" />{{ __('Hisob-faktura') }}</a>
</div>
<div class="card overflow-x-auto">
    <table class="w-full">
        <thead><tr><th class="th">№</th><th class="th">{{ __('Sana') }}</th><th class="th">{{ __('Bemor') }}</th><th class="th">{{ __('Jami') }}</th><th class="th">{{ __("To'langan") }}</th><th class="th">{{ __('Qoldiq') }}</th><th class="th">{{ __('Holat') }}</th></tr></thead>
        @forelse($invoices as $i)
            <tr><td class="td"><a class="link" href="{{ route('app.invoices.show', $i) }}">{{ $i->number }}</a></td><td class="td">{{ $i->created_at->format('d.m.Y H:i') }}</td>
                <td class="td">{{ $i->patient?->full_name }}</td><td class="td">{{ money($i->netTotal()) }}</td><td class="td">{{ money($i->paid) }}</td>
                <td class="td {{ $i->remaining() > 0 && $i->status !== 'canceled' ? 'text-red-600 font-medium' : '' }}">{{ $i->status === 'canceled' ? '—' : money($i->remaining()) }}</td>
                <td class="td"><x-badge :color="['paid' => 'green', 'partial' => 'yellow', 'unpaid' => 'red', 'canceled' => 'slate'][$i->status] ?? 'slate'">{{ $i->statusLabel() }}</x-badge></td></tr>
        @empty<tr><td class="td text-slate-500" colspan="7">{{ __('Hisob-fakturalar topilmadi') }}</td></tr>@endforelse
    </table>
</div>
<div class="mt-4">{{ $invoices->links() }}</div>
@endsection
