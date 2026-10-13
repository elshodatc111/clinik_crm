@extends('layouts.app')
@section('title', __('Klinikalar'))
@section('content')
@php($colors = ['trial' => 'blue', 'active' => 'green', 'view_only' => 'orange', 'blocked' => 'red', 'suspended' => 'slate'])
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <form class="flex gap-2" method="GET">
        <input name="q" value="{{ request('q') }}" class="input" placeholder="{{ __('Nomi, telefon, rahbar') }}">
        <select name="status" class="input"><option value="">{{ __('Barcha holatlar') }}</option>
            @foreach(\App\Models\Clinic::statusLabels() as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ __($l) }}</option>@endforeach
        </select>
        <button class="btn"><x-icon name="search" class="w-4 h-4" />{{ __('Qidirish') }}</button>
    </form>
    <a href="{{ route('admin.clinics.create') }}" class="btn btn-primary"><x-icon name="plus" class="w-4 h-4" />{{ __("Klinika qo'shish") }}</a>
</div>
<div class="card overflow-x-auto">
    <table class="w-full">
        <thead><tr><th class="th">{{ __('Klinika') }}</th><th class="th">{{ __('Rahbar / telefon') }}</th><th class="th">{{ __('Holat') }}</th><th class="th">{{ __('Bemorlar') }}</th><th class="th">{{ __('Balans') }}</th><th class="th">{{ __('Muddat') }}</th></tr></thead>
        <tbody>
        @forelse($clinics as $c)
            <tr>
                <td class="td"><a class="link font-medium" href="{{ route('admin.clinics.show', $c) }}">{{ $c->name }}</a></td>
                <td class="td">{{ $c->owner_name }}<div class="text-xs text-slate-500">{{ phone_fmt($c->phone) }}</div></td>
                <td class="td"><x-badge :color="$colors[$c->status] ?? 'slate'">{{ $c->statusLabel() }}</x-badge></td>
                <td class="td">{{ $counts[$c->id] ?? 0 }}</td>
                <td class="td">{{ money($c->balance) }}</td>
                <td class="td">{{ $c->status === 'trial' ? $c->trial_ends_at?->format('d.m.Y') : $c->paid_until?->format('d.m.Y') }}</td>
            </tr>
        @empty
            <tr><td class="td text-slate-500" colspan="6">{{ __('Klinikalar topilmadi') }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $clinics->links() }}</div>
@endsection
