{{-- Tezkor qidiruv oynasi (Ctrl+K yoki "/") --}}
<div x-data="gsearch()" x-show="open" x-cloak class="fixed inset-0 z-[70] flex items-start justify-center p-4 pt-[12vh]" @open-search.window="show()">
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="open = false"></div>
    <div class="relative w-full max-w-xl card shadow-2xl overflow-hidden" x-transition>
        <div class="flex items-center gap-3 px-4 border-b border-slate-100">
            <x-icon name="search" class="w-5 h-5 text-slate-400" />
            <input x-ref="i" x-model="q" @input.debounce.250ms="run()" @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="enter()"
                   class="flex-1 py-4 bg-transparent outline-none text-base" placeholder="{{ __('Bemor (F.I.Sh., telefon, JSHSHIR), hisob-faktura yoki sahifa...') }}" autocomplete="off">
            <kbd class="hidden sm:inline text-[10px] text-slate-400 border border-slate-200 rounded px-1.5 py-0.5">Esc</kbd>
        </div>
        <div class="max-h-[55vh] overflow-y-auto p-2">
            <template x-for="(g, gi) in groups()" :key="g.key">
                <div class="mb-1">
                    <div class="px-3 pt-2 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400" x-text="g.label"></div>
                    <template x-for="it in g.items" :key="it.url">
                        <a :href="it.url" @mouseenter="idx = it.i" class="flex items-center justify-between gap-3 px-3 py-2 rounded-xl text-sm" :class="idx === it.i ? 'bg-brand-soft' : ''">
                            <span class="font-medium text-slate-900 truncate" x-text="it.title"></span>
                            <span class="text-xs text-slate-500 truncate" x-text="it.sub"></span>
                        </a>
                    </template>
                </div>
            </template>
            <div x-show="q.length >= 2 && !busy && flat().length === 0" class="py-10 text-center text-sm text-slate-400">{{ __('Hech narsa topilmadi') }}</div>
            <div x-show="q.length < 2" class="py-8 text-center text-sm text-slate-400">{{ __('Kamida 2 ta belgi kiriting') }}</div>
        </div>
    </div>
</div>
@once
@push('scripts')
<script>
function gsearch() {
    return {
        open: false, q: '', busy: false, idx: 0, res: { patients: [], invoices: [], pages: [] }, seq: 0,
        init() {
            addEventListener('keydown', (e) => {
                const tag = (document.activeElement && document.activeElement.tagName) || '';
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); this.show(); }
                else if (e.key === '/' && !/INPUT|TEXTAREA|SELECT/.test(tag) && !document.activeElement.isContentEditable) { e.preventDefault(); this.show(); }
                else if (e.key === 'Escape') { this.open = false; }
            });
        },
        show() { this.open = true; this.$nextTick(() => this.$refs.i.focus()); },
        async run() {
            if (this.q.trim().length < 2) { this.res = { patients: [], invoices: [], pages: [] }; return; }
            const n = ++this.seq; this.busy = true;
            try {
                const r = await fetch(@json(route('app.search')) + '?q=' + encodeURIComponent(this.q.trim()), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                const j = await r.json(); if (n === this.seq) { this.res = j; this.idx = 0; }
            } catch (e) {} finally { if (n === this.seq) this.busy = false; }
        },
        groups() {
            let i = 0;
            const mk = (key, label, list) => ({ key, label, items: (list || []).map((x) => ({ ...x, i: i++ })) });
            return [mk('p', @json(__('Bemorlar')), this.res.patients), mk('i', @json(__('Hisob-fakturalar')), this.res.invoices), mk('g', @json(__('Sahifalar')), this.res.pages)].filter((g) => g.items.length);
        },
        flat() { return this.groups().flatMap((g) => g.items); },
        move(d) { const n = this.flat().length; if (n) this.idx = (this.idx + d + n) % n; },
        enter() { const it = this.flat()[this.idx]; if (it) location.href = it.url; },
    };
}
</script>
@endpush
@endonce
