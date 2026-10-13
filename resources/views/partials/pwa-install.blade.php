@once
@push('scripts')
<script>
function pwaInstall() {
    const standalone = () => matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
    const isIos = () => /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream;
    return {
        show: false, dismissed: false,
        init() {
            try { const t = +localStorage.getItem('pwa_dismiss'); this.dismissed = t && (Date.now() - t) < 7 * 864e5; } catch (e) {}
            const upd = () => { this.show = !standalone() && (!!window.__pwa.e || isIos()); };
            upd(); addEventListener('pwa-ready', upd); addEventListener('pwa-installed', () => this.show = false);
        },
        async install() {
            if (window.__pwa.e) { const e = window.__pwa.e; e.prompt(); try { await e.userChoice; } catch (x) {} window.__pwa.e = null; this.show = false; }
            else if (isIos()) { dispatchEvent(new Event('pwa-ios')); }
        },
        dismiss() { this.dismissed = true; try { localStorage.setItem('pwa_dismiss', Date.now()); } catch (e) {} },
    };
}
</script>
@endpush
@endonce
{{-- Ilovani o'rnatish: sarlavhadagi tugma (compact) yoki yuqori banner (banner) + iOS ko'rsatmasi --}}
@if(($mode ?? 'button') === 'button')
<div x-data="pwaInstall()" x-show="show" x-cloak>
    <button type="button" @click="install()" class="btn btn-sm hidden sm:inline-flex" title="{{ __("Ilovani o'rnatish") }}"><x-icon name="smartphone" class="w-4 h-4" /><span class="hidden lg:inline">{{ __("O'rnatish") }}</span></button>
</div>
@elseif($mode === 'banner')
<div x-data="pwaInstall()" x-show="show && !dismissed" x-cloak class="hero-grad text-white text-sm px-4 md:px-8 py-2.5 flex flex-wrap items-center justify-between gap-2">
    <span class="inline-flex items-center gap-2"><x-icon name="smartphone" class="w-4 h-4" />{{ __("Ilovani telefon yoki kompyuteringizga o'rnating — bir bosishda ochiladi, tezroq ishlaydi.") }}</span>
    <span class="flex items-center gap-2">
        <button type="button" @click="install()" class="rounded-lg bg-white text-slate-900 hover:bg-white/90 px-3.5 py-1 font-semibold">{{ __("O'rnatish") }}</button>
        <button type="button" @click="dismiss()" class="rounded-lg bg-white/15 hover:bg-white/25 px-2.5 py-1" aria-label="close"><x-icon name="x" class="w-4 h-4" /></button>
    </span>
</div>
@else
{{-- iOS (Safari) uchun ko'rsatma oynasi --}}
<div x-data="{ ios: false }" @pwa-ios.window="ios = true" x-show="ios" x-cloak class="fixed inset-0 z-[80] flex items-end sm:items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50" @click="ios = false"></div>
    <div class="relative card p-6 max-w-sm w-full shadow-2xl">
        <div class="flex items-center gap-3"><img src="/icons/icon-192.png" class="h-12 w-12 rounded-xl" alt=""><div class="font-semibold text-lg">{{ __("Ilovani o'rnatish") }}</div></div>
        <ol class="mt-4 space-y-2.5 text-sm text-slate-600 list-decimal pl-5">
            <li>{{ __('Safari pastidagi "Ulashish" (⬆) tugmasini bosing.') }}</li>
            <li>{{ __('"Bosh ekranga qo\'shish" (Add to Home Screen) ni tanlang.') }}</li>
            <li>{{ __('"Qo\'shish" tugmasini bosing.') }}</li>
        </ol>
        <button type="button" @click="ios = false" class="btn btn-primary w-full mt-5">{{ __('Tushundim') }}</button>
    </div>
</div>
@endif
