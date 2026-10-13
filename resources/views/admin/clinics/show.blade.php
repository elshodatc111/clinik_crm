@extends('layouts.app')
@section('title', $clinic->name)
@section('content')
@php($colors = ['trial' => 'blue', 'active' => 'green', 'view_only' => 'orange', 'blocked' => 'red', 'suspended' => 'slate'])
<div class="grid lg:grid-cols-3 gap-4">
    <div class="card p-5 lg:col-span-2">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="flex items-center gap-2"><h2 class="text-xl font-bold">{{ $clinic->name }}</h2><x-badge :color="$colors[$clinic->status] ?? 'slate'">{{ $clinic->statusLabel() }}</x-badge></div>
                <div class="text-sm text-slate-500 mt-1">{{ $clinic->owner_name }} · {{ phone_fmt($clinic->phone) }} @if($clinic->email) · {{ $clinic->email }} @endif</div>
            </div>
            <div class="flex gap-2">
                <a class="btn btn-sm" href="{{ route('admin.clinics.edit', $clinic) }}">{{ __('Tahrirlash') }}</a>
                <form method="POST" action="{{ route('admin.clinics.impersonate', $clinic) }}">@csrf<button class="btn btn-sm btn-primary">{{ __('Klinikaga kirish') }}</button></form>
            </div>
        </div>
        <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-2 mt-5 text-sm">
            <div><dt class="text-slate-500">{{ __("Ro'yxatdan o'tgan") }}</dt><dd>{{ $clinic->created_at->format('d.m.Y H:i') }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Demo tugashi') }}</dt><dd>{{ $clinic->trial_ends_at?->format('d.m.Y H:i') }} @if($clinic->status === 'trial')({{ __(':n kun', ['n' => $clinic->trialDaysLeft()]) }})@endif</dd></div>
            <div><dt class="text-slate-500">{{ __('Obuna tugashi') }}</dt><dd>{{ $clinic->paid_until?->format('d.m.Y H:i') ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">{{ __("Ulanish to'lovi to'langan") }}</dt><dd>{{ $clinic->setup_paid_at?->format('d.m.Y') ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Bemorlar soni') }}</dt><dd>{{ $count }} @if($plan)({{ $plan->name }} — {{ money($plan->monthly_price) }}/{{ __('oy') }})@endif</dd></div>
            <div><dt class="text-slate-500">{{ __('Balans') }}</dt><dd class="font-semibold">{{ money($clinic->balance) }}</dd></div>
            @if($clinic->blocked_at)<div><dt class="text-slate-500">{{ __('Bloklangan sana') }}</dt><dd>{{ $clinic->blocked_at->format('d.m.Y') }} ({{ __("o'chirish:") }} {{ $clinic->blocked_at->copy()->addDays(30)->format('d.m.Y') }})</dd></div>@endif
        </dl>
    </div>

    <div class="card p-5 space-y-5">
        <form method="POST" action="{{ route('admin.clinics.status', $clinic) }}" class="space-y-2">@csrf
            <div class="text-sm font-semibold">{{ __("Holatni o'zgartirish") }}</div>
            <select name="status" class="input">@foreach(\App\Models\Clinic::statusLabels() as $k => $l)<option value="{{ $k }}" @selected($clinic->status === $k)>{{ __($l) }}</option>@endforeach</select>
            <input name="reason" class="input" placeholder="{{ __('Sabab (ixtiyoriy)') }}">
            <button class="btn btn-sm w-full">{{ __('Saqlash') }}</button>
        </form>
        <form method="POST" action="{{ route('admin.clinics.extend', $clinic) }}" class="space-y-2">@csrf
            <div class="text-sm font-semibold">{{ __('Muddatni uzaytirish (kun)') }}</div>
            <input name="days" type="number" min="1" max="365" value="30" class="input">
            <button class="btn btn-sm w-full">{{ __('Uzaytirish') }}</button>
        </form>
        <form method="POST" action="{{ route('admin.clinics.balance', $clinic) }}" class="space-y-2">@csrf
            <div class="text-sm font-semibold">{{ __("Balansni qo'lda o'zgartirish") }}</div>
            <input name="amount" type="number" class="input" placeholder="{{ __("Summa (so'm, manfiy ham mumkin)") }}">
            <input name="note" class="input" placeholder="{{ __('Izoh') }}">
            <button class="btn btn-sm w-full">{{ __('Qo\'llash') }}</button>
        </form>
        <form method="POST" action="{{ route('admin.clinics.hold', $clinic) }}">@csrf
            <button class="btn btn-sm w-full">{{ $clinic->hold_deletion ? __("Avtomatik o'chirishni yoqish") : __("Avtomatik o'chirishni to'xtatish") }}</button>
        </form>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-4 mt-4">
    <div class="card overflow-hidden">
        <div class="px-4 py-3 font-semibold">{{ __('Xodimlar') }}</div>
        <table class="w-full">@foreach($users as $u)<tr><td class="td">{{ $u->name }}<div class="text-xs text-slate-500">{{ phone_fmt($u->phone) }}</div></td><td class="td">{{ $u->roleLabel() }}</td><td class="td">{{ $u->is_active ? '✓' : '✗' }}</td></tr>@endforeach</table>
    </div>
    <div class="card overflow-hidden">
        <div class="px-4 py-3 font-semibold">{{ __('Holatlar tarixi') }}</div>
        <table class="w-full">@forelse($logs as $l)<tr><td class="td whitespace-nowrap">{{ $l->created_at?->format('d.m.Y H:i') }}</td><td class="td">{{ $l->from_status }} → <b>{{ $l->to_status }}</b></td><td class="td text-slate-500">{{ $l->reason }}</td></tr>@empty<tr><td class="td text-slate-500">—</td></tr>@endforelse</table>
    </div>
    <div class="card overflow-hidden lg:col-span-2">
        <div class="px-4 py-3 font-semibold">{{ __('Balans harakatlari') }}</div>
        <table class="w-full"><thead><tr><th class="th">{{ __('Sana') }}</th><th class="th">{{ __('Turi') }}</th><th class="th">{{ __('Summa') }}</th><th class="th">{{ __('Qoldiq') }}</th><th class="th">{{ __('Izoh') }}</th></tr></thead>
        @forelse($transactions as $t)<tr><td class="td">{{ $t->created_at?->format('d.m.Y H:i') }}</td><td class="td">{{ $t->type }}</td><td class="td {{ $t->amount < 0 ? 'text-red-600' : 'text-green-700' }}">{{ ($t->amount > 0 ? '+' : '').number_format($t->amount, 0, '.', ' ') }}</td><td class="td">{{ number_format($t->balance_after, 0, '.', ' ') }}</td><td class="td text-slate-500">{{ $t->description }}</td></tr>@empty<tr><td class="td text-slate-500" colspan="5">—</td></tr>@endforelse</table>
    </div>
</div>

<div class="card p-5 mt-4 border-red-200">
    <div class="font-semibold text-red-700">{{ __("Klinikani butunlay o'chirish") }}</div>
    <p class="text-sm text-slate-600 mt-1">{{ __("Klinika va uning barcha ma'lumotlari (bemorlar, tashriflar, to'lovlar) qaytarib bo'lmas tarzda o'chiriladi. Pullik klinikalar uchun shifrlangan zaxira nusxa 30 kun saqlanadi.") }}</p>
    <form method="POST" action="{{ route('admin.clinics.destroy', $clinic) }}" class="mt-3 flex flex-wrap gap-2" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__('Rostdan ham o\'chirasizmi?')) }})">@csrf @method('DELETE')
        <input name="confirm_name" class="input max-w-xs" placeholder="{{ __('Tasdiqlash uchun klinika nomini yozing') }}">
        <button class="btn btn-danger">{{ __("O'chirish") }}</button>
    </form>
</div>
@endsection
