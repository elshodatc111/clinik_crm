@extends('layouts.app')
@section('title', __('Xodimlar va rollar'))
@section('content')
@php
    $me = auth()->user();
    $tone = ['clinic_admin' => 'bg-violet-50 text-violet-700', 'receptionist' => 'bg-cyan-50 text-cyan-700', 'doctor' => 'bg-blue-50 text-blue-700', 'cashier' => 'bg-emerald-50 text-emerald-700', 'laborant' => 'bg-amber-50 text-amber-700'];
    $roleNames = ['clinic_admin' => __('Admin / Direktor'), 'receptionist' => __('Qabulxona'), 'doctor' => __('Shifokor'), 'cashier' => __('Kassir'), 'laborant' => __('Laborant')];
@endphp
<div class="grid sm:grid-cols-2 xl:grid-cols-5 gap-3 mb-5">
    @foreach($roleNames as $k => $n)
        @php $cnt = $staff->filter(fn ($s) => $s->is_active && in_array($k, $s->allRoles(), true))->count(); @endphp
        <div class="card p-4 flex items-center gap-3">
            <span class="h-10 w-10 rounded-xl grid place-items-center {{ $tone[$k] }}"><x-icon :name="['clinic_admin' => 'shield', 'receptionist' => 'clipboard', 'doctor' => 'stethoscope', 'cashier' => 'banknote', 'laborant' => 'flask'][$k]" class="w-5 h-5" /></span>
            <div><div class="text-xs text-slate-500">{{ $n }}</div><div class="text-xl font-bold text-slate-900">{{ $cnt }}</div></div>
        </div>
    @endforeach
</div>

<div class="flex flex-wrap justify-between gap-2 mb-4">
    <button type="button" class="btn" onclick="document.getElementById('matrix').classList.toggle('hidden')"><x-icon name="eye" class="w-4 h-4" />{{ __('Rollar ruxsatlari jadvali') }}</button>
    <a href="{{ route('app.staff.create') }}" class="btn btn-primary"><x-icon name="plus" class="w-4 h-4" />{{ __("Xodim qo'shish") }}</a>
</div>

<div id="matrix" class="hidden card overflow-x-auto mb-5">
    <div class="px-5 py-4 font-semibold border-b border-slate-100 flex items-center gap-2"><x-icon name="shield" class="w-4 h-4" />{{ __('Qaysi rol qaysi bo\'limni ko\'ra oladi') }}</div>
    <table class="w-full">
        <thead><tr><th class="th">{{ __("Bo'lim") }}</th>@foreach($roleNames as $n)<th class="th text-center">{{ $n }}</th>@endforeach</tr></thead>
        @foreach($matrix as [$label, $perm])
            <tr><td class="td">{{ __($label) }}</td>
                @foreach($perm as $ok)<td class="td text-center">@if($ok)<span class="text-emerald-600 inline-block"><x-icon name="check" class="w-4 h-4" /></span>@else<span class="text-slate-300">—</span>@endif</td>@endforeach
            </tr>
        @endforeach
    </table>
    <div class="px-5 py-3 text-xs text-slate-500">{{ __("Xodimga asosiy roldan tashqari qo'shimcha ruxsat berilsa, u ikkala rolning bo'limlarini ko'radi.") }}</div>
</div>

<div class="card overflow-x-auto">
    <table class="w-full">
        <thead><tr><th class="th">{{ __('F.I.Sh.') }}</th><th class="th">{{ __('Telefon (login)') }}</th><th class="th">{{ __('Rollar') }}</th><th class="th">{{ __('Mutaxassislik') }}</th><th class="th">{{ __('Ish haqi') }}</th><th class="th">{{ __('Holat') }}</th><th class="th"></th></tr></thead>
        @foreach($staff as $s)
            <tr>
                <td class="td"><div class="flex items-center gap-3"><span class="h-9 w-9 rounded-full bg-slate-100 grid place-items-center text-sm font-semibold text-slate-600">{{ mb_strtoupper(mb_substr($s->name, 0, 1)) }}</span><div><div class="font-medium text-slate-900">{{ $s->name }}</div>@if($s->email)<div class="text-xs text-slate-500">{{ $s->email }}</div>@endif</div></div></td>
                <td class="td whitespace-nowrap">{{ phone_fmt($s->phone) }}</td>
                <td class="td">
                    <div class="flex flex-wrap gap-1.5">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $tone[$s->role] ?? 'bg-slate-100 text-slate-600' }}">{{ $s->roleLabel() }}</span>
                        @foreach($s->extraRoles() as $er)<span class="inline-flex items-center rounded-full border border-dashed border-slate-300 px-2.5 py-0.5 text-xs text-slate-600" title="{{ __("Qo'shimcha ruxsat") }}">+ {{ __(\App\Models\User::assignableRoles()[$er]) }}</span>@endforeach
                    </div>
                </td>
                <td class="td text-sm">{{ $s->specialty }} {{ $s->room ? '· '.__('xona').' '.$s->room : '' }}</td>
                <td class="td text-xs">{{ __(\App\Services\PayrollService::types()[$s->pay_type ?? 'none'] ?? '—') }}</td>
                <td class="td">@if($s->is_active)<x-badge color="green">{{ __('Faol') }}</x-badge>@else<x-badge color="red">{{ __('Faol emas') }}</x-badge>@endif</td>
                <td class="td text-right">
                    @if($s->role !== 'clinic_admin' || $s->position === 'admin' || $me->isDirector() || $s->id === $me->id)
                        <a class="btn btn-sm" href="{{ route('app.staff.edit', $s) }}"><x-icon name="pencil" class="w-3.5 h-3.5" />{{ __('Tahrirlash') }}</a>
                    @endif
                </td>
            </tr>
        @endforeach
    </table>
</div>
@endsection
