@extends('layouts.app')
@section('title', __('SMS jurnali'))
@section('content')
<div class="card overflow-x-auto">
    <table class="w-full">
        <thead><tr><th class="th">{{ __('Vaqt') }}</th><th class="th">{{ __('Telefon') }}</th><th class="th">{{ __('Xabar') }}</th><th class="th">{{ __("Holat") }}</th><th class="th">{{ __("Hodisa") }}</th></tr></thead>
        @foreach($logs as $l)
            <tr><td class="td whitespace-nowrap">{{ $l->created_at?->format('d.m.Y H:i') }}</td><td class="td">{{ phone_fmt($l->phone) }}</td><td class="td">{{ $l->message }}</td>
                <td class="td"><x-badge :color="['sent' => 'green', 'failed' => 'red', 'skipped' => 'yellow'][$l->status] ?? 'slate'">{{ $l->status }}</x-badge></td><td class="td text-xs text-slate-500">{{ $l->event ? __(\App\Support\SmsEvents::all()[$l->event]['label'] ?? $l->event) : '' }}</td></tr>
        @endforeach
    </table>
</div>
<div class="mt-4">{{ $logs->links() }}</div>
@endsection
