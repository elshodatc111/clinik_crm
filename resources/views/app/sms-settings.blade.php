@extends('layouts.app')
@section('title', __('SMS sozlamalari'))
@section('content')
@php
    $varLabels = ['name' => 'Bemor ismi', 'date' => 'Sana va vaqt', 'doctor' => 'Shifokor', 'clinic' => 'Klinika', 'address' => 'Manzil', 'n' => 'Navbat raqami'];
    $stColor = ['approved' => 'green', 'moderation' => 'yellow', 'rejected' => 'red', 'draft' => 'slate'];
    $stIcon = ['approved' => 'badge-check', 'moderation' => 'clock', 'rejected' => 'x', 'draft' => 'pencil'];
    $approvedCount = collect($events)->filter(fn ($e) => $e['state']['status'] === 'approved')->count();
@endphp
<div class="max-w-5xl space-y-5">

    {{-- Holat va asosiy tugma --}}
    <div class="card overflow-hidden">
        <div class="p-5 md:p-6 flex flex-wrap items-center justify-between gap-4 {{ $enabled ? '' : 'bg-slate-50' }}">
            <div class="flex items-center gap-4">
                <span class="h-12 w-12 rounded-2xl grid place-items-center {{ $enabled && $connected ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' }}"><x-icon name="message" class="w-6 h-6" /></span>
                <div>
                    <div class="font-semibold text-lg">{{ $enabled ? __('SMS xizmati yoqilgan') : __("SMS xizmati o'chirilgan") }}</div>
                    <div class="text-sm text-slate-500 mt-0.5">
                        @if(! $connected){{ __('Eskiz akkaunti ulanmagan — SMS yuborilmaydi.') }}
                        @elseif(! $enabled){{ __("Bemorlarga hech qanday SMS yuborilmaydi.") }}
                        @else{{ __(':a / :b shablon tasdiqlangan', ['a' => $approvedCount, 'b' => count($events)]) }}@endif
                    </div>
                </div>
            </div>
            <form method="POST" action="{{ route('app.sms.settings.toggle') }}" class="flex items-center gap-3">
                @csrf
                <input type="hidden" name="enabled" value="{{ $enabled ? 0 : 1 }}">
                <button class="btn {{ $enabled ? '' : 'btn-primary' }}" @if($enabled) onclick="return confirm({{ \Illuminate\Support\Js::from(__("SMS xizmati o'chirilsinmi? Bemorlarga SMS yuborilmaydi.")) }})" @endif>
                    <x-icon name="{{ $enabled ? 'lock' : 'check' }}" class="w-4 h-4" />{{ $enabled ? __("O'chirish") : __('Yoqish') }}
                </button>
            </form>
        </div>
        <div class="grid grid-cols-3 divide-x divide-slate-100 border-t border-slate-100 text-center text-sm">
            <div class="py-3"><div class="text-xl font-bold text-emerald-600">{{ $stats['sent'] }}</div><div class="text-xs text-slate-500">{{ __('Yuborilgan (30 kun)') }}</div></div>
            <div class="py-3"><div class="text-xl font-bold {{ $stats['failed'] ? 'text-red-600' : '' }}">{{ $stats['failed'] }}</div><div class="text-xs text-slate-500">{{ __('Xato') }}</div></div>
            <div class="py-3"><div class="text-xl font-bold {{ $stats['skipped'] ? 'text-amber-600' : '' }}">{{ $stats['skipped'] }}</div><div class="text-xs text-slate-500">{{ __("O'tkazib yuborilgan") }}</div></div>
        </div>
    </div>

    {{-- Eskiz akkaunti --}}
    <form method="POST" action="{{ route('app.sms.settings.update') }}" class="card p-6 space-y-4">
        @csrf @method('PUT')
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="font-semibold flex items-center gap-2"><x-icon name="settings" class="w-4 h-4 text-brand" />{{ __('Eskiz akkaunti') }}</div>
            <x-badge :color="$connected ? 'green' : 'yellow'">{{ $connected ? __('Ulangan') : __('Ulanmagan') }}</x-badge>
        </div>
        <p class="text-sm text-slate-500">{{ __("Bemorlarga SMS faqat klinikangizning o'z Eskiz akkaunti orqali yuboriladi va xarajat sizning Eskiz balansingizdan yechiladi. Akkauntni my.eskiz.uz saytida oching.") }}</p>
        <div class="grid md:grid-cols-3 gap-4">
            <x-field name="eskiz_email" type="email" :label="__('Eskiz email (login)')" :value="$clinic->setting('eskiz_email')" />
            <x-field name="eskiz_password" type="password" :label="__('Eskiz parol')" :hint="$connected ? __('O\'zgartirmaslik uchun bo\'sh qoldiring') : null" />
            <x-field name="eskiz_from" :label="__('Jo\'natuvchi nomi (from)')" :value="$clinic->setting('eskiz_from', '4546')" :hint="__('Eskiz tasdiqlagan nom. Standart: 4546')" />
        </div>
        <x-field name="sms_need_approval" type="checkbox" :label="__('Faqat Eskiz tasdiqlagan shablonlar bilan yuborish (tavsiya etiladi)')" :value="$needApproval" />
        <div class="flex flex-wrap gap-2">
            <button class="btn btn-primary"><x-icon name="check" class="w-4 h-4" />{{ __('Saqlash') }}</button>
            @if($connected)
                <button type="submit" formaction="{{ route('app.sms.settings.connection') }}" formmethod="POST" name="_method" value="POST" class="btn"><x-icon name="shield" class="w-4 h-4" />{{ __('Ulanishni tekshirish') }}</button>
            @endif
        </div>
    </form>

    {{-- Shablonlar --}}
    <div>
        <div class="flex items-center gap-2 mb-1"><x-icon name="file-text" class="w-5 h-5 text-brand" /><h2 class="font-semibold text-lg">{{ __('SMS shablonlari va yuborilish holatlari') }}</h2></div>
        <p class="text-sm text-slate-500 mb-4">{{ __("Eskiz faqat oldindan tasdiqlangan matnlarni yuboradi. Har bir shablonni Eskizga yuboring, tasdiqlangach \"Tekshirish\" tugmasi bilan holatini yangilang — shundan so'ng SMS avtomatik yuboriladi.") }}</p>
        <div class="grid lg:grid-cols-2 gap-4">
        @foreach($events as $key => $e)
            @php
                $st = $e['state'];
                $locked = $st['status'] === 'moderation';
            @endphp
            <form method="POST" action="{{ route('app.sms.tpl.save', $key) }}" class="card p-5 flex flex-col gap-3" x-data="tpl({{ \Illuminate\Support\Js::from($st['text']) }}, {{ $st['enabled'] ? 'true' : 'false' }})">
                @csrf
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3 min-w-0">
                        <span class="h-10 w-10 rounded-xl bg-blue-50 text-blue-600 grid place-items-center shrink-0"><x-icon :name="$e['icon']" class="w-5 h-5" /></span>
                        <div class="min-w-0"><div class="font-semibold leading-tight">{{ __($e['label']) }}</div><div class="text-xs text-slate-500 mt-0.5">{{ __($e['when']) }}</div></div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0" title="{{ __('Yoqish / o\'chirish') }}">
                        <input type="checkbox" name="enabled" value="1" x-model="on" class="sr-only peer">
                        <span class="w-10 h-6 rounded-full bg-slate-300 peer-checked:bg-brand transition"></span>
                        <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-4"></span>
                    </label>
                </div>

                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <x-badge :color="$stColor[$st['status']]"><x-icon :name="$stIcon[$st['status']]" class="w-3 h-3 mr-1" />{{ __(\App\Support\SmsEvents::statusLabel($st['status'])) }}</x-badge>
                    @if($st['checked_at'])<span class="text-slate-400">{{ __('Tekshirilgan') }}: {{ \Carbon\Carbon::parse($st['checked_at'])->format('d.m H:i') }}</span>@endif
                </div>
                @if($st['status'] === 'rejected')<div class="text-xs rounded-lg bg-red-50 text-red-700 px-3 py-2">{{ __('Eskiz shablonni rad etdi.') }} {{ $st['note'] }} {{ __("Matnni o'zgartirib, qayta yuboring.") }}</div>@endif

                <div>
                    <textarea name="text" rows="4" x-model="text" x-ref="ta" class="input" @if($locked) readonly @endif></textarea>
                    <div class="flex flex-wrap items-center justify-between gap-2 mt-1.5">
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($e['vars'] as $v)
                                <button type="button" @click="ins('{{ '{'.$v.'}' }}')" class="text-[11px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 hover:bg-brand hover:text-white transition" title="{{ __($varLabels[$v] ?? $v) }}">{{ '{'.$v.'}' }}</button>
                            @endforeach
                        </div>
                        <span class="text-[11px] text-slate-400" x-text="count()"></span>
                    </div>
                </div>

                <div class="rounded-xl bg-slate-50 px-3.5 py-2.5 text-sm text-slate-600 border border-dashed border-slate-200">
                    <div class="text-[10px] uppercase tracking-wider text-slate-400 mb-1">{{ __("Bemor ko'radigan SMS") }}</div>
                    <div x-text="preview()" class="whitespace-pre-line"></div>
                </div>

                <div class="flex flex-wrap gap-2 mt-auto pt-1">
                    <button class="btn btn-sm"><x-icon name="check" class="w-3.5 h-3.5" />{{ __('Saqlash') }}</button>
                    @if($connected)
                        <button type="submit" formaction="{{ route('app.sms.tpl.submit', $key) }}" class="btn btn-sm btn-primary"><x-icon name="send" class="w-3.5 h-3.5" />{{ __('Eskizga yuborish') }}</button>
                        <button type="submit" formaction="{{ route('app.sms.tpl.check', $key) }}" class="btn btn-sm"><x-icon name="badge-check" class="w-3.5 h-3.5" />{{ __('Tekshirish') }}</button>
                    @endif
                    @if($st['status'] !== 'approved')
                        <button type="submit" formaction="{{ route('app.sms.tpl.mark', $key) }}" class="btn btn-sm !border-transparent !shadow-none text-slate-500" onclick="return confirm({{ \Illuminate\Support\Js::from(__("Shablon Eskiz kabinetida allaqachon tasdiqlanganmi?")) }})">{{ __('Tasdiqlangan deb belgilash') }}</button>
                    @endif
                </div>
            </form>
        @endforeach
        </div>
    </div>

    @if($connected)
    <form method="POST" action="{{ route('app.sms.settings.test') }}" class="card p-6 flex flex-wrap items-end gap-3">
        @csrf
        <div class="flex-1 min-w-[220px]"><x-field name="test_phone" :label="__('Sinov SMS yuborish (telefon)')" placeholder="+998901234567" /></div>
        <button class="btn"><x-icon name="message" class="w-4 h-4" />{{ __('Yuborish') }}</button>
    </form>
    @endif
</div>
@endsection
@push('scripts')
<script>
const SMS_SAMPLE = @json($sample);
function tpl(text, on) {
    return {
        text, on,
        ins(v) { const t = this.$refs.ta; if (t.readOnly) return; const s = t.selectionStart ?? this.text.length, e = t.selectionEnd ?? s; this.text = this.text.slice(0, s) + v + this.text.slice(e); this.$nextTick(() => { t.focus(); t.setSelectionRange(s + v.length, s + v.length); }); },
        preview() { return this.text.replace(/\{(\w+)\}/g, (m, k) => SMS_SAMPLE[k] ?? m); },
        count() { const p = this.preview(); const u = /[^\x00-\x7F]/.test(p); const one = u ? 70 : 160, multi = u ? 67 : 153; const n = p.length; const seg = n <= one ? 1 : Math.ceil(n / multi); return n + ' belgi · ' + seg + ' SMS'; },
    };
}
</script>
@endpush
