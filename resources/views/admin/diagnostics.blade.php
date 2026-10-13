@extends('layouts.app')
@section('title', __('Klinikalar kamchiliklari'))
@section('content')
@php
    $dot = ['danger' => 'bg-red-500', 'warning' => 'bg-amber-500', 'info' => 'bg-blue-400'];
    $bad = $rows->filter(fn ($r) => count($r['issues']))->count();
@endphp
<div class="flex flex-wrap items-center justify-between gap-3">
    <p class="text-sm text-slate-500 max-w-2xl">{{ __("Har bir klinika sozlamalari va ishlashi avtomatik tekshiriladi. Kamchiliklarni klinika admin/direktoriga ilova ichida xabar qilib yuborishingiz mumkin. Tizim buni har kuni ertalab o'zi ham qiladi.") }}</p>
    <form method="POST" action="{{ route('admin.diagnostics.all') }}" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__('Barcha klinikalarga kamchiliklar bo\'yicha xabar yuborilsinmi?')) }})">@csrf
        <button class="btn btn-primary"><x-icon name="send" class="w-4 h-4" />{{ __('Hammasiga yuborish') }} ({{ $bad }})</button></form>
</div>

<div class="mt-5 space-y-3">
@foreach($rows as $r)
    @php $c = $r['clinic']; @endphp
    <div class="card p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <span class="h-10 w-10 rounded-xl grid place-items-center {{ count($r['issues']) === 0 ? 'bg-emerald-50 text-emerald-600' : ($r['danger'] ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600') }}"><x-icon :name="count($r['issues']) === 0 ? 'check' : 'alert'" class="w-5 h-5" /></span>
                <div class="min-w-0">
                    <a href="{{ route('admin.clinics.show', $c) }}" class="font-semibold hover:underline">{{ $c->name }}</a>
                    <div class="text-xs text-slate-500">{{ $c->statusLabel() }} · {{ $c->patientCount() }} {{ __('bemor') }}</div>
                </div>
            </div>
            @if(count($r['issues']))
                <form method="POST" action="{{ route('admin.diagnostics.clinic', $c) }}">@csrf<button class="btn btn-sm"><x-icon name="send" class="w-3.5 h-3.5" />{{ __('Xabar yuborish') }}</button></form>
            @else
                <x-badge color="green">{{ __('Kamchilik yo\'q') }}</x-badge>
            @endif
        </div>
        @if(count($r['issues']))
            <ul class="mt-3 grid md:grid-cols-2 gap-x-6 gap-y-2">
                @foreach($r['issues'] as $i)
                    <li class="text-sm flex items-start gap-2"><span class="mt-1.5 h-2 w-2 rounded-full shrink-0 {{ $dot[$i['level']] ?? 'bg-slate-400' }}"></span><span><b class="font-medium">{{ $i['title'] }}</b> <span class="text-slate-500">— {{ $i['body'] }}</span></span></li>
                @endforeach
            </ul>
        @endif
    </div>
@endforeach
</div>
@endsection
