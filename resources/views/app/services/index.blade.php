@extends('layouts.app')
@section('title', __('Xizmatlar va narxlar'))
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <form method="GET" class="flex gap-2"><input name="q" value="{{ request('q') }}" class="input w-64" placeholder="{{ __('Xizmat nomi') }}"><button class="btn"><x-icon name="search" class="w-4 h-4" />{{ __('Qidirish') }}</button></form>
    <a href="{{ route('app.services.create') }}" class="btn btn-primary"><x-icon name="plus" class="w-4 h-4" />{{ __("Xizmat qo'shish") }}</a>
</div>
<div class="card overflow-x-auto">
    <table class="w-full">
        <thead><tr><th class="th">{{ __('Nomi') }}</th><th class="th">{{ __('Guruh') }}</th><th class="th">{{ __('Narx') }}</th><th class="th">{{ __('Davomiyligi') }}</th><th class="th">{{ __('Faol') }}</th><th class="th"></th></tr></thead>
        @forelse($services as $s)
            <tr><td class="td">{{ $s->name }}</td><td class="td">{{ $s->group }}</td><td class="td">{{ money($s->price) }}</td><td class="td">{{ $s->duration_min }} {{ __('daq.') }}</td><td class="td">{{ $s->is_active ? '✓' : '✗' }}</td>
            <td class="td text-right whitespace-nowrap"><a class="btn btn-sm" href="{{ route('app.services.edit', $s) }}">{{ __('Tahrirlash') }}</a>
            <form method="POST" action="{{ route('app.services.destroy', $s) }}" class="inline" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__('O\'chirasizmi?')) }})">@csrf @method('DELETE')<button class="btn btn-sm btn-danger">✕</button></form></td></tr>
        @empty<tr><td class="td text-slate-500" colspan="6">{{ __("Xizmatlar yo'q. Birinchi xizmatni qo'shing.") }}</td></tr>@endforelse
    </table>
</div>
<div class="mt-4">{{ $services->links() }}</div>
@endsection
