@extends('layouts.app')
@section('title', __('Tizim holati'))
@section('content')
@php
    $tone = ['ok' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20', 'warn' => 'bg-amber-50 text-amber-700 ring-amber-600/20', 'bad' => 'bg-red-50 text-red-700 ring-red-600/20'];
    $dot = ['ok' => 'bg-emerald-500', 'warn' => 'bg-amber-500', 'bad' => 'bg-red-500'];
    $worst = collect($checks)->pluck(2)->contains('bad') ? 'bad' : (collect($checks)->pluck(2)->contains('warn') ? 'warn' : 'ok');
    $worstText = ['ok' => 'Hammasi joyida', 'warn' => "E'tibor talab qiladi", 'bad' => 'Muammo bor'];
@endphp

<div class="flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-3">
        <span class="relative flex h-3 w-3"><span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-60 {{ $dot[$worst] }}"></span><span class="relative inline-flex rounded-full h-3 w-3 {{ $dot[$worst] }}"></span></span>
        <div class="text-lg font-semibold">{{ __($worstText[$worst]) }}</div>
        <span class="text-xs text-slate-500">{{ now()->format('d.m.Y H:i:s') }}</span>
    </div>
    <div class="flex items-center gap-2">
        @foreach([6 => '6 '.__('soat'), 24 => '24 '.__('soat'), 72 => '3 '.__('kun'), 168 => '7 '.__('kun')] as $h => $l)
            <a href="{{ route('admin.system.index', ['h' => $h]) }}" class="btn {{ $hours === $h ? 'btn-primary' : '' }} !py-1.5 !px-3 text-sm">{{ $l }}</a>
        @endforeach
        <a href="{{ route('admin.system.index', ['h' => $hours]) }}" class="btn !py-1.5 !px-3 text-sm">{{ __('Yangilash') }}</a>
    </div>
</div>

@if(count($alerts))
<div class="card mt-4 p-4 space-y-2 border-l-4 {{ collect($alerts)->pluck(0)->contains('bad') ? 'border-red-500' : 'border-amber-500' }}">
    <div class="font-semibold flex items-center gap-2"><x-icon name="alert" class="w-4 h-4" />{{ __('Ogohlantirishlar') }}</div>
    @foreach($alerts as [$lvl, $msg])
        <div class="text-sm flex items-start gap-2"><span class="mt-1.5 h-2 w-2 rounded-full shrink-0 {{ $dot[$lvl] }}"></span><span>{{ __($msg) }}</span></div>
    @endforeach
</div>
@endif

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-5">
    <x-stat icon="activity" tone="blue" :label="__('So\'rovlar soni')" :value="number_format($stats['hits'], 0, '.', ' ')" :hint="__('Hozir').': '.$stats['rpm'].' '.__('so\'rov/daq').' · '.__('Eng yuqori').': '.$stats['peakRpm']" />
    <x-stat icon="clock" :tone="$stats['avg'] > 800 ? 'red' : ($stats['avg'] > 400 ? 'amber' : 'green')" :label="__('O\'rtacha javob vaqti')" :value="$stats['avg'].' ms'" :hint="__('Eng sekin').': '.$stats['max'].' ms'" />
    <x-stat icon="alert" :tone="$stats['errors'] > 0 ? 'red' : 'green'" :label="__('Server xatolari (5xx)')" :value="$stats['errors']" :hint="$stats['errorRate'].'%'" />
    <x-stat icon="building" tone="violet" :label="__('Muammoli klinikalar')" :value="$biz['clinics_block']" :hint="__('Bloklangan yoki faqat ko\'rish')" />
</div>

<div class="grid lg:grid-cols-2 gap-4 mt-5">
    <div class="card overflow-hidden">
        <div class="section-title"><x-icon name="activity" class="w-4 h-4 text-brand" />{{ __('Yuklama (so\'rovlar soni)') }}</div>
        <div class="p-5"><div class="h-60"><canvas id="cReq"></canvas></div></div>
    </div>
    <div class="card overflow-hidden">
        <div class="section-title"><x-icon name="clock" class="w-4 h-4 text-brand" />{{ __('O\'rtacha javob vaqti (ms)') }}</div>
        <div class="p-5"><div class="h-60"><canvas id="cAvg"></canvas></div></div>
    </div>
</div>

<div class="card mt-5 overflow-hidden" x-data="{ copied: false }">
    <div class="section-title justify-between"><span class="flex items-center gap-2"><x-icon name="server" class="w-4 h-4 text-brand" />{{ __('Fon jarayonlari (scheduler va worker)') }}</span>
        <span class="badge ring-1 ring-inset {{ $tone[$scheduler['status']] }}">{{ $scheduler['status'] === 'ok' ? __('Cron ishlayapti') : ($scheduler['status'] === 'warn' ? __('Cron kechikmoqda') : __('Cron ishlamayapti')) }}</span></div>
    @if($scheduler['status'] !== 'ok')
    <div class="mx-5 mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 space-y-2">
        <div class="font-medium">{{ __("Scheduler ishlamayapti — SMS eslatmalar, billing, zaxira va bronlarni bo'shatish bajarilmaydi.") }}</div>
        <div>{{ __('cPanel → Cron Jobs (Задания cron) → «Каждую минуту» (* * * * *) tanlab, quyidagi buyruqni kiriting:') }}</div>
        <div class="flex items-center gap-2"><code class="flex-1 rounded bg-white border border-amber-200 px-3 py-2 text-xs break-all select-all">{{ $scheduler['command'] }}</code>
            <button type="button" class="btn btn-sm" @click="navigator.clipboard && navigator.clipboard.writeText(@js($scheduler['command'])); copied = true"><x-icon name="copy" class="w-3.5 h-3.5" /><span x-text="copied ? @js(__('Nusxalandi')) : @js(__('Nusxalash'))"></span></button></div>
        <div class="text-xs text-amber-800">{{ __("PHP versiyasi (ea-php83 kabi) hosting sozlamasidagi versiyaga mos bo'lsin. Cron qo'shilgach 1–2 daqiqada bu yerda yashil belgi chiqadi.") }}</div>
    </div>
    @endif
    <div class="overflow-x-auto"><table class="w-full text-sm">
        <thead><tr><th class="th">{{ __('Vazifa') }}</th><th class="th">{{ __('Jadval') }}</th><th class="th">{{ __('Oxirgi ishga tushish') }}</th><th class="th">{{ __('Holat') }}</th><th class="th">{{ __('Davomiyligi') }}</th><th class="th">{{ __('Ishga tushgan / xato') }}</th><th class="th"></th></tr></thead>
        @foreach($tasks as $t)
            <tr class="border-t border-slate-100">
                <td class="td"><div class="font-medium">{{ __($t['label']) }}</div><div class="text-[11px] text-slate-400 font-mono">{{ $t['name'] }}</div>@if($t['message'])<div class="text-xs text-red-600 mt-0.5">{{ $t['message'] }}</div>@endif</td>
                <td class="td text-slate-500 whitespace-nowrap">{{ __($t['every']) }}</td>
                <td class="td whitespace-nowrap">{{ $t['last'] ? $t['last']->format('d.m H:i:s') : '—' }}<div class="text-xs text-slate-400">{{ \App\Services\TaskMonitor::ago($t['age']) }}</div></td>
                <td class="td"><span class="badge ring-1 ring-inset {{ $tone[$t['status']] }}">{{ __($t['note']) }}</span></td>
                <td class="td whitespace-nowrap text-slate-500">{{ $t['ms'] }} ms</td>
                <td class="td whitespace-nowrap">{{ $t['runs'] }} / <span class="{{ $t['fails'] ? 'text-red-600 font-medium' : '' }}">{{ $t['fails'] }}</span></td>
                <td class="td text-right">@if($t['run'])<form method="POST" action="{{ route('admin.system.run', $t['name']) }}">@csrf<button class="btn btn-sm"><x-icon name="play" class="w-3.5 h-3.5" />{{ __('Hozir ishga tushirish') }}</button></form>@endif</td>
            </tr>
        @endforeach
    </table></div>
    <div class="px-5 py-3 text-xs text-slate-500 border-t border-slate-100">{{ __("Navbat (queue) worker: loyiha hozir «sync» rejimida — alohida worker kerak emas. Yurak urishi har daqiqa ishlaydigan vazifadan olinadi.") }}</div>
</div>

<div class="card mt-5 overflow-hidden">
    <div class="section-title"><x-icon name="shield" class="w-4 h-4 text-brand" />{{ __('Server salomatligi') }}</div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 p-5">
        @foreach($checks as [$k, $label, $st, $val, $hint])
            <div class="rounded-xl border border-slate-100 p-4 flex items-start justify-between gap-3">
                <div class="min-w-0"><div class="text-xs text-slate-500">{{ __($label) }}</div><div class="font-semibold mt-0.5">{{ $val }}</div>@if($hint)<div class="text-xs text-slate-400 mt-0.5 truncate">{{ __($hint) }}</div>@endif</div>
                <span class="badge ring-1 ring-inset {{ $tone[$st] }}">{{ $st === 'ok' ? 'OK' : ($st === 'warn' ? '!' : 'X') }}</span>
            </div>
        @endforeach
        <div class="rounded-xl border border-slate-100 p-4"><div class="text-xs text-slate-500">PHP / Laravel</div><div class="font-semibold mt-0.5">{{ $env['php'] }} / {{ $env['laravel'] }}</div><div class="text-xs text-slate-400 mt-0.5">{{ $env['env'] }} · {{ $env['tz'] }}</div></div>
        @if($env['debug'] && $env['env'] === 'production')
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ __('APP_DEBUG yoqilgan! Ishlab chiqarishda uni o\'chiring.') }}</div>
        @endif
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-4 mt-5">
    <div class="card overflow-hidden">
        <div class="section-title"><x-icon name="clock-3" class="w-4 h-4 text-brand" />{{ __('Eng sekin sahifalar') }}</div>
        <table class="w-full text-sm"><thead><tr class="text-left text-xs text-slate-500"><th class="px-5 py-2">{{ __('Sahifa') }}</th><th class="px-2">{{ __('So\'rov') }}</th><th class="px-2">{{ __('O\'rtacha') }}</th><th class="px-5 text-right">{{ __('Maks.') }}</th></tr></thead><tbody>
        @forelse($stats['slowRoutes'] as $r)
            <tr class="border-t border-slate-100"><td class="px-5 py-2 font-mono text-xs">{{ $r->route }}</td><td class="px-2">{{ $r->h }}</td><td class="px-2 font-semibold {{ $r->avg_ms > 800 ? 'text-red-600' : '' }}">{{ (int) $r->avg_ms }} ms</td><td class="px-5 text-right text-slate-500">{{ $r->mx }} ms</td></tr>
        @empty
            <tr><td colspan="4" class="px-5 py-8 text-center text-slate-400">{{ __('Hali ma\'lumot yo\'q') }}</td></tr>
        @endforelse
        </tbody></table>
    </div>
    <div class="card overflow-hidden">
        <div class="section-title"><x-icon name="building" class="w-4 h-4 text-brand" />{{ __('Eng faol klinikalar (yuklama)') }}</div>
        <table class="w-full text-sm"><thead><tr class="text-left text-xs text-slate-500"><th class="px-5 py-2">{{ __('Klinika') }}</th><th class="px-2">{{ __('So\'rov') }}</th><th class="px-5 text-right">{{ __('O\'rtacha') }}</th></tr></thead><tbody>
        @forelse($stats['clinics'] as $c)
            <tr class="border-t border-slate-100"><td class="px-5 py-2 font-medium">{{ $stats['names'][$c->clinic_id] ?? ('#'.$c->clinic_id) }}</td><td class="px-2">{{ number_format($c->h, 0, '.', ' ') }}</td><td class="px-5 text-right">{{ (int) $c->avg_ms }} ms</td></tr>
        @empty
            <tr><td colspan="3" class="px-5 py-8 text-center text-slate-400">{{ __('Hali ma\'lumot yo\'q') }}</td></tr>
        @endforelse
        </tbody></table>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-4 mt-5">
    <div class="card p-5">
        <div class="font-semibold flex items-center gap-2"><x-icon name="message" class="w-4 h-4 text-brand" />{{ __('Xizmatlar (24 soat)') }}</div>
        <dl class="mt-3 space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">{{ __('SMS yuborilgan') }}</dt><dd class="font-semibold">{{ $biz['sms_total'] }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">{{ __('SMS xato') }}</dt><dd class="font-semibold {{ $biz['sms_failed'] ? 'text-red-600' : '' }}">{{ $biz['sms_failed'] }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">{{ __('Onlayn bron (to\'lov kutilmoqda)') }}</dt><dd class="font-semibold">{{ $biz['booking_pending'] }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">{{ __('Muddati o\'tgan bronlar') }}</dt><dd class="font-semibold">{{ $biz['booking_expired'] }}</dd></div>
        </dl>
    </div>
    <div class="card p-5 lg:col-span-2">
        <div class="font-semibold flex items-center gap-2"><x-icon name="layers" class="w-4 h-4 text-brand" />{{ __("Ma'lumotlar bazasi") }} @if($db['size'])<span class="text-xs text-slate-500 font-normal">· {{ $db['size'] }} MB (top-8) @if($db['threads']) · {{ __('Ulanishlar') }}: {{ $db['threads'] }} @endif</span>@endif</div>
        <div class="mt-3 grid sm:grid-cols-2 gap-x-6 gap-y-1.5 text-sm">
            @forelse($db['tables'] as $t)
                <div class="flex justify-between border-b border-slate-50 py-1"><span class="font-mono text-xs">{{ $t->n }}</span><span class="text-slate-500">{{ number_format((int) $t->r, 0, '.', ' ') }} · {{ $t->mb }} MB</span></div>
            @empty
                <div class="text-slate-400">{{ __('Ma\'lumot yo\'q') }}</div>
            @endforelse
        </div>
    </div>
</div>

<div class="card mt-5 overflow-hidden">
    <div class="section-title justify-between"><span class="flex items-center gap-2"><x-icon name="alert" class="w-4 h-4 text-brand" />{{ __('So\'nggi xatolar') }}</span>
        @if($sysErrors->count() || $slow->count())<form method="POST" action="{{ route('admin.system.clear') }}" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__('Jurnal tozalansinmi?')) }})">@csrf @method('DELETE')<button class="text-xs text-slate-500 hover:text-red-600">{{ __('Tozalash') }}</button></form>@endif</div>
    <div class="divide-y divide-slate-100">
        @forelse($sysErrors as $e)
            <div class="px-5 py-3 text-sm"><div class="flex flex-wrap justify-between gap-2"><span class="font-medium break-all">{{ $e->message }}</span><span class="text-xs text-slate-400">{{ \Carbon\Carbon::parse($e->created_at)->format('d.m H:i') }}</span></div><div class="text-xs text-slate-500 mt-0.5 font-mono">{{ $e->method }} {{ $e->route }} · {{ $e->file }}@if($e->clinic_id) · {{ __('klinika') }} #{{ $e->clinic_id }}@endif</div></div>
        @empty
            <div class="px-5 py-8 text-center text-slate-400">{{ __('Xatolar yo\'q — ajoyib!') }}</div>
        @endforelse
    </div>
    @if($slow->count())
        <div class="px-5 py-2 text-xs font-semibold text-slate-500 bg-slate-50">{{ __('Sekin so\'rovlar') }} (&gt; {{ \App\Services\SystemMonitor::SLOW_MS }} ms)</div>
        <div class="divide-y divide-slate-100">
            @foreach($slow as $e)
                <div class="px-5 py-2 text-sm flex flex-wrap justify-between gap-2"><span class="font-mono text-xs">{{ $e->method }} {{ $e->route }}</span><span><b class="text-amber-600">{{ $e->duration_ms }} ms</b> <span class="text-xs text-slate-400">{{ \Carbon\Carbon::parse($e->created_at)->format('d.m H:i') }}</span></span></div>
            @endforeach
        </div>
    @endif
</div>
@endsection
@push('scripts')
@include('partials.charts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    KC.bar('cReq', @json($stats['labels']), @json($stats['reqs']), @json(__("So'rovlar")), false, false);
    KC.area('cAvg', @json($stats['labels']), @json($stats['avgs']), 'ms', false);
});
</script>
@endpush
