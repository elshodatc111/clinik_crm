@extends('layouts.app')
@section('title', __('Obuna va balans'))
@section('content')
@php($colors = ['trial' => 'blue', 'active' => 'green', 'view_only' => 'orange', 'blocked' => 'red', 'suspended' => 'slate'])
<div x-data="billingPay({{ (int) $needed }}, {{ $errors->manual->any() ? 'true' : 'false' }})">
<div class="grid lg:grid-cols-3 gap-4">
    <div class="card p-5">
        <div class="text-xs text-slate-500">{{ __('Balans') }}</div>
        <div class="text-3xl font-extrabold mt-1">{{ money($clinic->balance) }}</div>
        <div class="mt-3"><x-badge :color="$colors[$clinic->status] ?? 'slate'">{{ $clinic->statusLabel() }}</x-badge></div>
        @if($clinic->status === 'trial')
            <div class="text-sm text-slate-600 mt-2">{{ __('Demo tugashi') }}: {{ $clinic->trial_ends_at?->format('d.m.Y') }} ({{ __(':n kun qoldi', ['n' => $clinic->trialDaysLeft()]) }})</div>
        @elseif($clinic->paid_until)
            <div class="text-sm text-slate-600 mt-2">{{ __('Obuna tugashi') }}: {{ $clinic->paid_until->format('d.m.Y') }}</div>
        @endif
        @if($clinic->status === 'blocked' && $clinic->blocked_at)
            <div class="text-sm text-red-600 mt-2">{{ __("Ma'lumotlar o'chiriladi:") }} {{ $clinic->blocked_at->copy()->addDays(30)->format('d.m.Y') }}</div>
        @endif
        <div class="text-sm text-slate-600 mt-3">{{ __('Bemorlar') }}: <b>{{ $count }}</b> @if($plan)· {{ $plan->name }}: <b>{{ money($price) }}</b>/{{ __('oy') }}@endif</div>
    </div>

    <div class="card p-5 lg:col-span-2">
        <div class="font-semibold mb-3">{{ __("Balansni to'ldirish") }}</div>
        @if(! $paymeReady && ! $cardReady)<div class="rounded bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm px-3 py-2">{{ __("To'lov usullari hali sozlanmagan. Administrator bilan bog'laning.") }}</div>@endif
        @if($paymeReady || $cardReady)
        <div class="flex flex-wrap items-center gap-2 mb-3">
            <span class="text-xs text-slate-500">{{ __('Summa') }}:</span>
            @foreach([100000, 500000, 1000000, 2000000, 5000000] as $q)<button type="button" class="btn btn-sm" @click="amount = {{ $q }}">{{ number_format($q, 0, '.', ' ') }}</button>@endforeach
            @if($needed > 10000)<button type="button" class="btn btn-sm !border-brand text-brand" @click="amount = {{ $needed }}">{{ __('Kerakli summa') }}: {{ number_format($needed, 0, '.', ' ') }}</button>@endif
        </div>
        <div class="grid md:grid-cols-2 gap-4">
            @if($paymeReady)
            <form method="POST" action="{{ route('app.billing.topup') }}" class="rounded-xl border border-slate-200 p-4 space-y-3">@csrf
                <div class="font-medium flex items-center gap-2"><x-icon name="credit-card" class="w-4 h-4 text-brand" />{{ __("Payme orqali (avtomatik)") }}</div>
                <input type="number" name="amount" x-model="amount" min="1000" class="input">
                <button class="btn btn-primary w-full whitespace-nowrap">{{ __("Payme orqali to'lash") }}</button>
                <p class="text-xs text-slate-500">{{ __("To'lov zumda balansga tushadi.") }}</p>
            </form>
            @endif
            @if($cardReady)
            <div class="rounded-xl border border-slate-200 p-4 space-y-3">
                <div class="font-medium flex items-center gap-2"><x-icon name="banknote" class="w-4 h-4 text-brand" />{{ __("Kartaga o'tkazish (qo'lda tasdiqlanadi)") }}</div>
                <div class="rounded-xl p-4 text-white hero-grad">
                    <div class="text-[11px] opacity-80">{{ $card['bank'] ?: __('Karta') }}</div>
                    <div class="mt-1 flex items-center justify-between gap-2">
                        <span class="font-mono text-lg tracking-wider" id="cardNo">{{ $card['formatted'] }}</span>
                        <button type="button" class="rounded-lg bg-white/20 hover:bg-white/30 px-2 py-1 text-xs flex items-center gap-1" @click="copyCard('{{ $card['number'] }}')"><x-icon name="copy" class="w-3.5 h-3.5" /><span x-text="copied ? @js(__('Nusxalandi')) : @js(__('Nusxalash'))"></span></button>
                    </div>
                    @if($card['holder'])<div class="mt-2 text-sm opacity-90">{{ $card['holder'] }}</div>@endif
                </div>
                @if($card['note'])<p class="text-xs text-slate-600 rounded bg-slate-50 px-3 py-2">{{ $card['note'] }}</p>@endif
                <button type="button" class="btn btn-primary w-full" @click="open = true"><x-icon name="upload" class="w-4 h-4" />{{ __("To'lov qildim") }}</button>
                <p class="text-xs text-slate-500">{{ __("Kartaga pul o'tkazing, so'ng \"To'lov qildim\" tugmasini bosib chek rasmini yuboring. Tekshirilgach balans to'ldiriladi.") }}</p>
            </div>
            @endif
        </div>
        <p class="text-xs text-slate-500 mt-3">{{ __("Oylik obuna to'lovi balansdan avtomatik yechiladi. Balansda mablag' bo'lishini oldindan ta'minlang.") }}</p>
        @endif
    </div>
</div>

@if($manual->count())
<div class="card overflow-hidden mt-4">
    <div class="px-4 py-3 font-semibold flex items-center gap-2"><x-icon name="receipt" class="w-4 h-4 text-brand" />{{ __("Karta to'lovlari") }}</div>
    <div class="overflow-x-auto"><table class="w-full text-sm">
        @foreach($manual as $m)
            <tr class="border-t border-slate-100">
                <td class="td whitespace-nowrap">{{ $m->created_at?->format('d.m.Y H:i') }}</td>
                <td class="td font-medium whitespace-nowrap">{{ money($m->status === 'approved' ? ($m->approved_amount ?? $m->amount) : $m->amount) }}</td>
                <td class="td"><x-badge :color="\App\Models\ManualPayment::statusColor($m->status)">{{ __(\App\Models\ManualPayment::statusLabel($m->status)) }}</x-badge>
                    @if($m->status === 'rejected')<div class="mt-1 text-xs text-red-600"><b>{{ __('Sabab') }}:</b> {{ $m->reject_reason }}</div>@endif</td>
                <td class="td text-right whitespace-nowrap">
                    <a class="link text-xs" target="_blank" href="{{ route('app.billing.manual.receipt', $m) }}">{{ __('Chek') }}</a>
                    @if($m->status === 'pending')
                        <form method="POST" action="{{ route('app.billing.manual.cancel', $m) }}" class="inline" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__("So'rov bekor qilinsinmi?")) }})">@csrf @method('DELETE')<button class="text-xs text-red-600 ml-3">{{ __('Bekor qilish') }}</button></form>
                    @elseif($m->status === 'rejected' && $cardReady)
                        <button type="button" class="text-xs link ml-3" @click="amount = {{ (int) $m->amount }}; open = true">{{ __('Qayta yuborish') }}</button>
                    @endif
                </td>
            </tr>
        @endforeach
    </table></div>
</div>
@endif

@if($clinic->status === 'trial')
<div class="card p-5 mt-4 border-blue-200">
    <div class="font-semibold">{{ __("Obunaga o'tish") }}</div>
    <p class="text-sm text-slate-600 mt-1">{{ __("Bir martalik ulanish to'lovi:") }} <b>{{ money($setupFee) }}</b>. {{ __("To'lovdan keyin birinchi oy bepul.") }}</p>
    @if($clinic->balance >= $setupFee)
        <form method="POST" action="{{ route('app.billing.subscribe') }}" class="mt-3" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__('Balansdan ulanish to\'lovi yechiladi. Davom etasizmi?')) }})">@csrf<button class="btn btn-primary">{{ __("Obunaga o'tish (balansdan yechish)") }}</button></form>
    @else
        <p class="mt-3 text-sm text-orange-700">{{ __("Balansda mablag' yetarli emas. Yetishmayotgan summa:") }} <b>{{ money($setupFee - $clinic->balance) }}</b></p>
    @endif
</div>
@endif

@if(in_array($clinic->status, ['view_only', 'blocked']))
<div class="card p-5 mt-4 border-orange-200">
    <p class="text-sm">{{ __("Obunani tiklash uchun balansda kamida bir oylik to'lov bo'lishi kerak:") }} <b>{{ money($price) }}</b>. {{ __("Balans to'ldirilishi bilan obuna avtomatik tiklanadi.") }}</p>
    <a href="{{ route('app.export.patients') }}" class="btn btn-sm mt-3">{{ __("Bemorlar ro'yxatini yuklab olish (Excel/CSV)") }}</a>
</div>
@endif

<div class="grid lg:grid-cols-2 gap-4 mt-4">
    <div class="card overflow-hidden">
        <div class="px-4 py-3 font-semibold">{{ __('Tariflar') }}</div>
        <table class="w-full">@foreach($plans as $p)
            <tr class="{{ $plan && $plan->id === $p->id ? 'bg-blue-50' : '' }}"><td class="td">{{ $p->name }}</td><td class="td">{{ $p->rangeLabel() }} {{ __('bemor') }}</td><td class="td text-right font-medium">{{ money($p->monthly_price) }}</td></tr>
        @endforeach</table>
    </div>
    <div class="card overflow-hidden">
        <div class="px-4 py-3 font-semibold">{{ __('Balans harakatlari') }}</div>
        <table class="w-full">@forelse($transactions as $t)
            <tr><td class="td whitespace-nowrap">{{ $t->created_at?->format('d.m.Y H:i') }}</td><td class="td text-slate-500">{{ $t->description }}</td><td class="td text-right {{ $t->amount < 0 ? 'text-red-600' : 'text-green-700' }}">{{ ($t->amount > 0 ? '+' : '').number_format($t->amount, 0, '.', ' ') }}</td></tr>
        @empty<tr><td class="td text-slate-500">—</td></tr>@endforelse</table>
    </div>
</div>

{{-- "To'lov qildim" oynasi --}}
@if($cardReady)
<div x-show="open" x-cloak class="fixed inset-0 z-50 grid place-items-center p-4" @keydown.escape.window="open = false">
    <div class="absolute inset-0 bg-slate-900/50" @click="open = false"></div>
    <form method="POST" action="{{ route('app.billing.manual') }}" enctype="multipart/form-data" class="relative card w-full max-w-lg p-6 space-y-4 max-h-[92vh] overflow-y-auto">
        @csrf
        <div class="flex items-center justify-between"><div class="font-semibold text-lg">{{ __("To'lov qildim") }}</div><button type="button" class="text-slate-400 hover:text-slate-700" @click="open = false"><x-icon name="x" class="w-5 h-5" /></button></div>
        <div class="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600">{{ __('Karta') }}: <b class="font-mono">{{ $card['formatted'] }}</b> {{ $card['holder'] }}</div>
        @if($errors->manual->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><ul class="list-disc pl-5">@foreach($errors->manual->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif
        <div class="grid sm:grid-cols-2 gap-4">
            <div><label class="block text-sm font-medium mb-1">{{ __("O'tkazilgan summa (so'm)") }} *</label><input type="number" name="amount" x-model="amount" min="1000" class="input" required></div>
            <div><label class="block text-sm font-medium mb-1">{{ __("To'lov sanasi") }} *</label><input type="date" name="paid_at" value="{{ old('paid_at', today()->toDateString()) }}" max="{{ today()->toDateString() }}" class="input" required></div>
            <div><label class="block text-sm font-medium mb-1">{{ __("Sizning kartangiz (oxirgi 4 raqam)") }}</label><input name="payer_card" value="{{ old('payer_card') }}" maxlength="4" inputmode="numeric" pattern="\d{4}" class="input" placeholder="1234"></div>
            <div><label class="block text-sm font-medium mb-1">{{ __('Izoh (ixtiyoriy)') }}</label><input name="note" value="{{ old('note') }}" maxlength="300" class="input"></div>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">{{ __("To'lov cheki (rasm yoki PDF)") }} *</label>
            <input type="file" name="receipt" accept="image/*,application/pdf" required @change="pick($event)" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm">
            <img x-show="preview" :src="preview" class="mt-3 max-h-48 rounded-lg border border-slate-200" alt="">
            <p class="text-xs text-slate-500 mt-1">{{ __('Chekda summa, sana va karta raqami aniq ko\'rinsin. Eng ko\'pi 5 MB.') }}</p>
        </div>
        <div class="flex gap-2"><button class="btn btn-primary"><x-icon name="check" class="w-4 h-4" />{{ __('Yuborish') }}</button><button type="button" class="btn" @click="open = false">{{ __('Bekor qilish') }}</button></div>
    </form>
</div>
@endif
</div>
@endsection
@push('scripts')
<script>
function billingPay(amount, open) {
    return {
        amount, open, copied: false, preview: null,
        copyCard(n) { (navigator.clipboard ? navigator.clipboard.writeText(n) : Promise.reject()).then(() => { this.copied = true; setTimeout(() => this.copied = false, 1800); }).catch(() => { const r = document.createRange(); r.selectNodeContents(document.getElementById('cardNo')); const s = getSelection(); s.removeAllRanges(); s.addRange(r); }); },
        pick(e) { const f = e.target.files[0]; this.preview = (f && f.type.startsWith('image/')) ? URL.createObjectURL(f) : null; },
    };
}
</script>
@endpush
