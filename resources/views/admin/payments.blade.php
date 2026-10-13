@extends('layouts.app')
@section('title', __("To'lov cheklari (karta)"))
@section('content')
@php
    $tabs = ['pending' => "Tekshirilmoqda", 'approved' => 'Tasdiqlangan', 'rejected' => 'Bekor qilingan', 'canceled' => 'Klinika bekor qilgan', 'all' => 'Hammasi'];
@endphp
<div class="max-w-6xl space-y-4">
    <div class="card p-5 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <span class="h-12 w-12 rounded-2xl bg-blue-50 text-blue-600 grid place-items-center"><x-icon name="credit-card" class="w-6 h-6" /></span>
            <div>
                @if($card['number'])
                    <div class="font-mono text-lg font-semibold tracking-wide">{{ $card['formatted'] }}</div>
                    <div class="text-sm text-slate-500">{{ $card['holder'] }} @if($card['bank'])· {{ $card['bank'] }}@endif · <x-badge :color="$enabled ? 'green' : 'slate'">{{ $enabled ? __('Yoqilgan') : __("O'chiq") }}</x-badge></div>
                @else
                    <div class="font-semibold">{{ __('Karta kiritilmagan') }}</div>
                    <div class="text-sm text-slate-500">{{ __("Klinikalar kartaga to'lay olishi uchun Sozlamalarda karta raqamini kiriting.") }}</div>
                @endif
            </div>
        </div>
        <a href="{{ route('admin.settings.edit') }}" class="btn"><x-icon name="settings" class="w-4 h-4" />{{ __('Kartani sozlash') }}</a>
    </div>

    <div class="flex flex-wrap gap-2">
        @foreach($tabs as $k => $l)
            <a href="{{ route('admin.payments.index', ['status' => $k] + (request('clinic_id') ? ['clinic_id' => request('clinic_id')] : [])) }}" class="px-3.5 py-1.5 rounded-full text-sm border {{ $status === $k ? 'bg-brand text-white border-transparent' : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300' }}">
                {{ __($l) }} @if($k !== 'all')<span class="ml-1 opacity-80">{{ $counts[$k] ?? 0 }}</span>@endif
            </a>
        @endforeach
    </div>

    @forelse($items as $p)
        @php($pdf = \Illuminate\Support\Str::endsWith($p->receipt_path, '.pdf'))
        <div class="card p-4 md:p-5" x-data="{ rej: false, amount: {{ (int) $p->amount }} }">
            <div class="flex flex-col md:flex-row gap-5">
                <a href="{{ route('admin.payments.receipt', $p) }}" target="_blank" rel="noopener" class="shrink-0 block w-full md:w-44 h-44 rounded-xl border border-slate-200 bg-slate-50 overflow-hidden grid place-items-center hover:ring-2 hover:ring-brand/40 transition">
                    @if($pdf)<span class="text-center text-slate-500 text-sm"><x-icon name="file-text" class="w-10 h-10 mx-auto mb-1" />PDF</span>
                    @else<img src="{{ route('admin.payments.receipt', $p) }}" class="w-full h-full object-cover" alt="{{ __('Chek') }}" loading="lazy">@endif
                </a>
                <div class="flex-1 min-w-0 space-y-3">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <a class="font-semibold text-lg link" href="{{ route('admin.clinics.show', $p->clinic_id) }}">{{ $p->clinic?->name ?? '#'.$p->clinic_id }}</a>
                            <div class="text-xs text-slate-500">{{ $p->user?->name }} · {{ phone_fmt($p->clinic?->phone) }} · {{ $p->created_at?->format('d.m.Y H:i') }}</div>
                        </div>
                        <x-badge :color="\App\Models\ManualPayment::statusColor($p->status)">{{ __(\App\Models\ManualPayment::statusLabel($p->status)) }}</x-badge>
                    </div>
                    <dl class="grid grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
                        <div><dt class="text-xs text-slate-500">{{ __('Ko\'rsatilgan summa') }}</dt><dd class="font-bold text-lg">{{ money($p->amount) }}</dd></div>
                        <div><dt class="text-xs text-slate-500">{{ __("To'langan sana") }}</dt><dd class="font-medium">{{ $p->paid_at?->format('d.m.Y') }}</dd></div>
                        <div><dt class="text-xs text-slate-500">{{ __("To'lovchi karta (oxirgi 4)") }}</dt><dd class="font-medium">{{ $p->payer_card ?: '—' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">{{ __("Qaysi kartaga") }}</dt><dd class="font-mono text-xs">{{ $p->card_number ? \App\Services\ManualPaymentService::cardFormatted($p->card_number) : '—' }}</dd></div>
                    </dl>
                    @if($p->note)<div class="text-sm rounded-lg bg-slate-50 px-3 py-2 text-slate-600">{{ $p->note }}</div>@endif

                    @if($p->status === 'pending')
                        <div class="flex flex-wrap items-end gap-3 pt-1" x-show="!rej">
                            <form method="POST" action="{{ route('admin.payments.approve', $p) }}" class="flex flex-wrap items-end gap-2" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__("To'lov tasdiqlansin va klinika balansiga qo'shilsinmi?")) }})">@csrf
                                <div><label class="block text-xs text-slate-500 mb-1">{{ __("Tasdiqlanadigan summa (so'm)") }}</label><input type="number" name="amount" x-model="amount" min="1000" class="input w-44" required></div>
                                <button class="btn btn-primary"><x-icon name="check" class="w-4 h-4" />{{ __('Tasdiqlash') }}</button>
                            </form>
                            <button type="button" class="btn !text-red-600" @click="rej = true"><x-icon name="x" class="w-4 h-4" />{{ __('Bekor qilish') }}</button>
                        </div>
                        <form method="POST" action="{{ route('admin.payments.reject', $p) }}" x-show="rej" x-cloak class="space-y-2 pt-1">@csrf
                            <label class="block text-sm font-medium">{{ __('Bekor qilish sababi (klinikaga ko\'rsatiladi)') }} *</label>
                            <textarea name="reason" rows="2" class="input" required minlength="5" maxlength="500" placeholder="{{ __("Masalan: chekda summa ko'rinmayapti / pul tushmagan / boshqa kartaga o'tkazilgan") }}"></textarea>
                            <div class="flex flex-wrap gap-1.5 text-[11px]">
                                @foreach([__("Pul kartaga tushmagan"), __("Chek aniq ko'rinmayapti"), __("Summa chekdagi bilan mos emas"), __("Chek soxta yoki boshqa to'lovga tegishli"), __("Boshqa kartaga o'tkazilgan")] as $quick)
                                    <button type="button" class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 hover:bg-brand hover:text-white transition" @click="$el.closest('form').querySelector('textarea').value = {{ \Illuminate\Support\Js::from($quick) }}">{{ $quick }}</button>
                                @endforeach
                            </div>
                            <div class="flex gap-2"><button class="btn !bg-red-600 !text-white !border-transparent">{{ __("Bekor qilishni tasdiqlash") }}</button><button type="button" class="btn" @click="rej = false">{{ __('Orqaga') }}</button></div>
                        </form>
                    @elseif($p->status === 'approved')
                        <div class="text-sm text-emerald-700">{{ __('Tasdiqlangan summa') }}: <b>{{ money($p->approved_amount ?? $p->amount) }}</b> · {{ $p->reviewer?->name }} · {{ $p->reviewed_at?->format('d.m.Y H:i') }}</div>
                    @elseif($p->status === 'rejected')
                        <div class="text-sm rounded-lg bg-red-50 text-red-700 px-3 py-2"><b>{{ __('Sabab') }}:</b> {{ $p->reject_reason }} <span class="text-red-400">· {{ $p->reviewer?->name }} · {{ $p->reviewed_at?->format('d.m.Y H:i') }}</span></div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="card p-10 text-center text-slate-500"><x-icon name="receipt" class="w-10 h-10 mx-auto mb-2 text-slate-300" />{{ __("Bu bo'limda to'lovlar yo'q.") }}</div>
    @endforelse
    <div>{{ $items->links() }}</div>
</div>
@endsection
