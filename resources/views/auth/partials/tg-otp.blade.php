{{-- Telegram orqali tasdiqlash. $flow: register | reset. Kod kelgach #tg-code-box ochiladi. --}}
<div x-data="tgOtp('{{ $flow }}', '{{ route('otp.telegram') }}', '{{ route('otp.telegram.status') }}')" x-init="start()" class="rounded-xl border border-sky-200 bg-sky-50 p-4 mb-4 dark:bg-sky-950/30 dark:border-sky-900">
    <div class="flex items-start gap-3">
        <span class="h-9 w-9 shrink-0 rounded-full bg-sky-500 text-white grid place-items-center">
            <svg viewBox="0 0 24 24" class="w-5 h-5" fill="currentColor"><path d="M9.8 15.3l-.4 4.2c.6 0 .9-.3 1.2-.6l2.9-2.7 6 4.4c1.1.6 1.9.3 2.2-1L23.9 4c.4-1.6-.6-2.3-1.7-1.9L1.5 10c-1.6.6-1.6 1.5-.3 1.9l5.3 1.6L18.8 6c.6-.4 1.1-.2.7.2z"/></svg>
        </span>
        <div class="min-w-0 flex-1">
            <div class="font-semibold text-sm">{{ __('Telegram orqali tasdiqlash') }}</div>
            <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5" x-text="msg">{{ __("Tugmani bosing — bot ochiladi. «Start» va «Raqamni ulashish» ni bossangiz, kod o'sha yerga keladi.") }}</p>
        </div>
    </div>
    <div class="mt-3 flex flex-wrap items-center gap-2">
        <a x-show="link" x-cloak :href="link" target="_blank" rel="noopener" class="btn btn-primary btn-sm">{{ __('Telegramni ochish') }}</a>
        <button type="button" class="btn btn-sm" @click="start()" :disabled="busy" x-show="!link || state==='failed' || state==='expired' || state==='sent'" x-cloak>
            <span x-text="state==='sent' ? '{{ __('Kodni qayta yuborish') }}' : '{{ __('Telegram orqali kod olish') }}'"></span>
        </button>
    </div>
</div>
<script>
function tgOtp(flow, url, statusUrl) {
    return {
        link: '', token: '', state: 'idle', msg: '', busy: false, timer: null,
        async start() {
            if (this.busy) return;
            this.busy = true; clearInterval(this.timer);
            try {
                const r = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ flow }) });
                const j = await r.json();
                this.msg = j.message || '';
                if (!j.ok) { this.state = 'failed'; return; }
                if (j.sent) { this.state = 'sent'; this.reveal(); return; }
                this.link = j.link; this.token = j.token; this.state = 'pending';
                this.timer = setInterval(() => this.poll(), 2500);
            } catch (e) { this.state = 'failed'; this.msg = {!! json_encode(__("Aloqa xatosi. Qayta urinib ko'ring.")) !!}; }
            finally { this.busy = false; }
        },
        async poll() {
            try {
                const r = await fetch(statusUrl + '?token=' + encodeURIComponent(this.token), { headers: { 'Accept': 'application/json' } });
                const j = await r.json();
                this.state = j.state; this.msg = j.message;
                if (['sent', 'failed', 'expired'].includes(j.state)) { clearInterval(this.timer); if (j.state === 'sent') { this.link = ''; this.reveal(); } }
            } catch (e) {}
        },
        reveal() {
            const b = document.getElementById('tg-code-box'); if (b) { b.classList.remove('hidden'); const i = b.querySelector('input[name=code]'); if (i) i.focus(); }
        }
    };
}
</script>
