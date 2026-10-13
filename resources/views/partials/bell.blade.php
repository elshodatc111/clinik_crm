{{-- Bildirishnoma qo'ng'iroqchasi (tugma + ochiladigan ro'yxat) --}}
<div x-data="bell()" class="relative" @keydown.escape.window="open = false">
    <button type="button" @click="open = !open" class="btn btn-sm !px-2 relative" aria-label="{{ __('Bildirishnomalar') }}">
        <x-icon name="bell" class="w-4 h-4" />
        <span x-show="unread > 0" x-cloak x-text="unread > 9 ? '9+' : unread" class="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold grid place-items-center ring-2 ring-white"></span>
    </button>
    <div x-show="open" x-cloak x-transition.origin.top.right @click.outside="open = false" class="absolute right-0 mt-2 w-[22rem] max-w-[92vw] card shadow-xl overflow-hidden z-50">
        <div class="px-4 py-3 flex items-center justify-between border-b border-slate-100">
            <div class="font-semibold">{{ __('Bildirishnomalar') }}</div>
            <button type="button" @click="readAll()" x-show="unread > 0" class="text-xs link">{{ __("Hammasini o'qish") }}</button>
        </div>
        <div class="max-h-96 overflow-y-auto divide-y divide-slate-100">
            <template x-for="n in items" :key="n.id">
                <button type="button" @click="go(n)" class="w-full text-left px-4 py-3 flex gap-3 hover:bg-slate-50 transition" :class="n.read ? '' : 'bg-blue-50/50'">
                    <span class="mt-1 h-2 w-2 rounded-full shrink-0" :class="{ 'bg-blue-500': n.level === 'info', 'bg-emerald-500': n.level === 'success', 'bg-amber-500': n.level === 'warning', 'bg-red-500': n.level === 'danger' }"></span>
                    <span class="min-w-0"><span class="block text-sm font-medium text-slate-900" x-text="n.title"></span><span class="block text-xs text-slate-500 mt-0.5 line-clamp-2" x-text="n.body" x-show="n.body"></span><span class="block text-[11px] text-slate-400 mt-1" x-text="n.time"></span></span>
                </button>
            </template>
            <div x-show="items.length === 0" class="px-4 py-10 text-center text-sm text-slate-400">{{ __("Bildirishnomalar yo'q") }}</div>
        </div>
        <a href="{{ route('notifications.index') }}" class="block text-center text-sm link py-2.5 border-t border-slate-100">{{ __("Barchasini ko'rish") }}</a>
    </div>
    {{-- Yangi xabar kelganda qisqa ogohlantirish --}}
    <div x-show="toast" x-cloak x-transition class="fixed bottom-5 right-5 z-[60] card shadow-xl px-4 py-3 max-w-sm flex items-start gap-3 cursor-pointer" @click="toast && go(toast)">
        <span class="h-8 w-8 rounded-lg bg-blue-50 text-blue-600 grid place-items-center shrink-0"><x-icon name="bell" class="w-4 h-4" /></span>
        <div class="min-w-0"><div class="text-sm font-semibold" x-text="toast && toast.title"></div><div class="text-xs text-slate-500 mt-0.5 line-clamp-2" x-text="toast && toast.body"></div></div>
    </div>
</div>
@once
@push('scripts')
<script>
function bell() {
    const csrf = () => document.querySelector('meta[name=csrf-token]').content;
    return {
        open: false, unread: 0, items: [], toast: null, first: true, seen: 0, timer: null,
        init() { this.load(); setInterval(() => { if (!document.hidden) this.load(); }, 45000); },
        async load() {
            try {
                const r = await fetch(@json(route('notifications.feed')), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (!r.ok) return;
                const j = await r.json();
                const newest = j.items.length ? j.items[0].id : 0;
                if (!this.first && newest > this.seen && j.items[0] && !j.items[0].read) { this.toast = j.items[0]; clearTimeout(this.timer); this.timer = setTimeout(() => this.toast = null, 7000); }
                this.seen = Math.max(this.seen, newest); this.first = false;
                this.unread = j.unread; this.items = j.items;
            } catch (e) {}
        },
        async mark(n) { try { await fetch('/notifications/' + n.id + '/read', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' }, credentials: 'same-origin' }); } catch (e) {} },
        async go(n) { await this.mark(n); this.toast = null; if (n.url) { location.href = n.url; } else { n.read = true; this.load(); } },
        async readAll() { try { await fetch(@json(route('notifications.readall')), { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' }, credentials: 'same-origin' }); } catch (e) {} this.load(); },
    };
}
</script>
@endpush
@endonce
