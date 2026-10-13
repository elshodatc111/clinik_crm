@extends('layouts.app')
@section('title', __('Bildirishnomalar'))
@section('content')
@php
    $tone = ['info' => 'bg-blue-50 text-blue-600', 'success' => 'bg-emerald-50 text-emerald-600', 'warning' => 'bg-amber-50 text-amber-600', 'danger' => 'bg-red-50 text-red-600'];
    $icon = ['info' => 'info', 'success' => 'check', 'warning' => 'alert', 'danger' => 'alert'];
@endphp
<div class="max-w-3xl">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div class="flex rounded-xl bg-slate-100 p-0.5 text-sm font-medium">
            <a href="{{ route('notifications.index') }}" class="px-4 py-1.5 rounded-lg {{ ! $filter ? 'bg-white shadow text-brand' : 'text-slate-500' }}">{{ __('Hammasi') }}</a>
            <a href="{{ route('notifications.index', ['f' => 'unread']) }}" class="px-4 py-1.5 rounded-lg {{ $filter === 'unread' ? 'bg-white shadow text-brand' : 'text-slate-500' }}">{{ __("O'qilmagan") }}</a>
        </div>
        <form method="POST" action="{{ route('notifications.readall') }}">@csrf<button class="btn btn-sm"><x-icon name="check" class="w-4 h-4" />{{ __("Hammasini o'qilgan qilish") }}</button></form>
    </div>
    <div class="card divide-y divide-slate-100 overflow-hidden">
        @forelse($items as $n)
            <form method="POST" action="{{ route('notifications.read', $n) }}" class="block">
                @csrf
                <button class="w-full text-left flex items-start gap-4 px-5 py-4 hover:bg-slate-50 transition {{ $n->read_at ? '' : 'bg-blue-50/40' }}">
                    <span class="h-10 w-10 rounded-xl grid place-items-center shrink-0 {{ $tone[$n->level] ?? $tone['info'] }}"><x-icon :name="$icon[$n->level] ?? 'info'" class="w-5 h-5" /></span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center gap-2"><span class="font-semibold text-slate-900">{{ $n->title }}</span>@unless($n->read_at)<span class="h-2 w-2 rounded-full bg-brand"></span>@endunless</span>
                        @if($n->body)<span class="block text-sm text-slate-600 mt-0.5">{{ $n->body }}</span>@endif
                        <span class="block text-xs text-slate-400 mt-1">{{ $n->created_at?->diffForHumans() }}@if($n->url) · {{ __('Ochish') }} →@endif</span>
                    </span>
                </button>
            </form>
        @empty
            <div class="py-16 text-center text-slate-400"><x-illu name="empty" class="mx-auto w-40 mb-3" />{{ __("Bildirishnomalar yo'q") }}</div>
        @endforelse
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
</div>
@endsection
