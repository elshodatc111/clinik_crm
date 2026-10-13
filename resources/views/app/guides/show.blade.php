@extends('layouts.app')
@section('title', \App\Support\Guides::text($guide['title']))
@section('content')
@php($clinic = view()->shared('clinic'))
<div class="flex flex-wrap items-center justify-between gap-3 mb-4 no-print">
    @if($count > 1)<a href="{{ route('app.guides.index') }}" class="btn btn-sm"><x-icon name="chevron-right" class="w-4 h-4 rotate-180" />{{ __('Barcha yo\'riqnomalar') }}</a>@else<span></span>@endif
    <div class="flex gap-2">
        <button type="button" class="btn" onclick="window.print()"><x-icon name="printer" class="w-4 h-4" />{{ __('Chop etish') }}</button>
        <button type="button" id="pdfBtn" class="btn btn-primary"><x-icon name="download" class="w-4 h-4" /><span>{{ __('PDF yuklab olish') }}</span></button>
    </div>
</div>

<div id="guideDoc" class="card p-6 md:p-8 max-w-4xl bg-white">
    <div class="flex flex-wrap items-center gap-6 pb-6 border-b border-slate-200">
        <div class="flex-1 min-w-[240px]">
            <div class="text-xs font-semibold uppercase tracking-wider text-brand">{{ $clinic?->name ?? $brand['name'] }} · {{ __("Foydalanish yo'riqnomasi") }}</div>
            <h2 class="mt-1 text-2xl md:text-3xl font-bold text-slate-900">{{ \App\Support\Guides::text($guide['title']) }}</h2>
            <p class="mt-3 text-slate-600">{{ \App\Support\Guides::text($guide['intro']) }}</p>
        </div>
        <div class="w-48 h-36 rounded-2xl bg-brand-soft p-2"><x-illu :name="$guide['illu']" /></div>
    </div>

    <div class="mt-6 grid md:grid-cols-[200px_1fr] gap-8">
        <nav class="hidden md:block text-sm space-y-1 sticky top-24 self-start" data-html2canvas-ignore="true">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">{{ __('Mundarija') }}</div>
            @foreach($guide['sections'] as $i => $s)<a href="#s{{ $i }}" class="block rounded-lg px-3 py-1.5 text-slate-600 hover:bg-slate-50 hover:text-brand">{{ \App\Support\Guides::text($s['title']) }}</a>@endforeach
        </nav>
        <div class="space-y-8">
            @foreach($guide['sections'] as $i => $s)
                <section id="s{{ $i }}" style="break-inside: avoid">
                    <h3 class="text-lg font-bold text-slate-900 mb-3">{{ \App\Support\Guides::text($s['title']) }}</h3>
                    <ol class="space-y-2.5">
                        @foreach($s['steps'] as $n => $st)
                            <li class="flex gap-3"><span class="mt-0.5 h-6 w-6 shrink-0 rounded-full bg-brand-soft text-brand text-xs font-bold grid place-items-center">{{ $n + 1 }}</span><span class="text-slate-700 leading-relaxed">{{ \App\Support\Guides::text($st) }}</span></li>
                        @endforeach
                    </ol>
                    @if($s['tip'])
                        <div class="mt-3 flex gap-3 rounded-xl bg-amber-50 p-3.5 text-sm text-amber-900"><x-icon name="info" class="w-5 h-5 text-amber-600" /><div><b>{{ __('Maslahat') }}:</b> {{ \App\Support\Guides::text($s['tip']) }}</div></div>
                    @endif
                </section>
            @endforeach
        </div>
    </div>
    <div class="mt-8 pt-4 border-t border-slate-200 text-xs text-slate-400 flex justify-between"><span>{{ $clinic?->name ?? $brand['name'] }}</span><span>{{ now()->format('d.m.Y') }}</span></div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
document.getElementById('pdfBtn').addEventListener('click', function () {
    const btn = this, label = btn.querySelector('span'), old = label.textContent;
    const el = document.getElementById('guideDoc');
    if (!window.html2pdf) { window.print(); return; }
    label.textContent = @json(__('Tayyorlanmoqda...')); btn.disabled = true;
    const wasDark = document.documentElement.classList.contains('dark');
    if (wasDark) document.documentElement.classList.remove('dark');
    html2pdf().set({
        margin: [10, 10, 12, 10], filename: @json(\Illuminate\Support\Str::slug($key.'-'.app()->getLocale()).'.pdf'),
        image: { type: 'jpeg', quality: 0.96 }, html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff' },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }, pagebreak: { mode: ['css', 'legacy'], avoid: 'section' }
    }).from(el).save().catch(() => window.print()).finally(() => { if (wasDark) document.documentElement.classList.add('dark'); label.textContent = old; btn.disabled = false; });
});
</script>
@endpush
@endsection
