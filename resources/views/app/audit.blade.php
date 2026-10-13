@extends('layouts.app')
@section('title', __('Harakatlar jurnali'))
@section('content')
<div class="card overflow-x-auto">
    <table class="w-full">
        <thead><tr><th class="th">{{ __('Vaqt') }}</th>@if($showClinic)<th class="th">{{ __('Klinika') }}</th>@endif<th class="th">{{ __('Foydalanuvchi') }}</th><th class="th">{{ __('Harakat') }}</th><th class="th">{{ __("Ob'ekt") }}</th><th class="th">{{ __("O'zgarishlar") }}</th><th class="th">IP</th></tr></thead>
        @foreach($logs as $l)
            <tr><td class="td whitespace-nowrap">{{ $l->created_at?->format('d.m.Y H:i:s') }}</td>
                @if($showClinic)<td class="td">{{ $l->clinic?->name }}</td>@endif
                <td class="td">{{ $l->user?->name ?? '—' }}</td>
                <td class="td"><x-badge :color="['created' => 'green', 'updated' => 'blue', 'deleted' => 'red'][$l->action] ?? 'slate'">{{ $l->action }}</x-badge></td>
                <td class="td">{{ $l->auditable_type }} #{{ $l->auditable_id }}</td>
                <td class="td text-xs text-slate-500 max-w-md truncate">{{ \Illuminate\Support\Str::limit(json_encode($l->changes, JSON_UNESCAPED_UNICODE), 160) }}</td>
                <td class="td text-xs">{{ $l->ip_address }}</td></tr>
        @endforeach
    </table>
</div>
<div class="mt-4">{{ $logs->links() }}</div>
@endsection
