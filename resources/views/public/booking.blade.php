@extends('layouts.booking')
@section('title', __('Onlayn qabulga yozilish'))
@section('content')
<div x-data="booking(@js($services), @js(['slots' => route('booking.slots', $clinic->slug), 'book' => route('booking.book', $clinic->slug)]))" class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-5">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ __('Onlayn qabulga yozilish') }}</h1>
            <p class="text-slate-500 mt-1">{{ __("Xizmat va shifokorni tanlang, to'lovni amalga oshiring — navbatingiz avtomatik band qilinadi.") }}</p>
        </div>

        {{-- 1. Xizmat --}}
        <section class="card p-5">
            <h2 class="font-semibold flex items-center gap-2"><span class="step" :class="service ? 'done' : ''">1</span>{{ __('Xizmatni tanlang') }}</h2>
            <div class="mt-4 grid sm:grid-cols-2 gap-3">
                <template x-for="s in services" :key="s.id">
                    <button type="button" @click="pickService(s)" class="text-left rounded-xl border p-4 transition hover:-translate-y-0.5 hover:shadow-md"
                            :class="service && service.id === s.id ? 'border-brand bg-brand-soft' : 'border-slate-200'">
                        <div class="font-medium text-slate-900" x-text="s.name"></div>
                        <div class="text-xs text-slate-500 mt-0.5"><span x-text="s.duration"></span> {{ __('daqiqa') }}</div>
                        <div class="mt-2 font-bold text-brand" x-text="money(s.price)"></div>
                    </button>
                </template>
            </div>
        </section>

        {{-- 2. Shifokor --}}
        <section class="card p-5" x-show="service" x-cloak>
            <h2 class="font-semibold flex items-center gap-2"><span class="step" :class="doctor ? 'done' : ''">2</span>{{ __('Shifokorni tanlang') }}</h2>
            <div class="mt-4 grid sm:grid-cols-2 gap-3">
                <template x-for="d in (service ? service.doctors : [])" :key="d.id">
                    <button type="button" @click="pickDoctor(d)" class="text-left rounded-xl border p-4 flex items-center gap-3 transition hover:-translate-y-0.5 hover:shadow-md"
                            :class="doctor && doctor.id === d.id ? 'border-brand bg-brand-soft' : 'border-slate-200'">
                        <span class="h-10 w-10 rounded-full bg-brand-soft text-brand grid place-items-center"><svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg></span>
                        <span><span class="block font-medium text-slate-900" x-text="d.name"></span><span class="block text-xs text-slate-500" x-text="d.specialty"></span></span>
                    </button>
                </template>
            </div>
        </section>

        {{-- 3. Sana va vaqt --}}
        <section class="card p-5" x-show="doctor" x-cloak>
            <h2 class="font-semibold flex items-center gap-2"><span class="step" :class="time ? 'done' : ''">3</span>{{ __('Sana va vaqtni tanlang') }}</h2>
            <input type="date" x-model="date" @change="loadSlots()" min="{{ $minDate }}" max="{{ $maxDate }}" class="input mt-4 max-w-xs">
            <div class="mt-4">
                <div x-show="loading" class="text-sm text-slate-500">{{ __('Yuklanmoqda...') }}</div>
                <div x-show="!loading && date && slots.length === 0" class="text-sm text-slate-500">{{ __("Bu kunda bo'sh vaqt yo'q. Boshqa kunni tanlang.") }}</div>
                <div class="flex flex-wrap gap-2">
                    <template x-for="t in slots" :key="t">
                        <button type="button" @click="time = t" class="px-4 py-2 rounded-lg border text-sm font-medium transition"
                                :class="time === t ? 'bg-brand text-white border-transparent' : 'border-slate-200 hover:border-brand'" x-text="t"></button>
                    </template>
                </div>
            </div>
        </section>

        {{-- 4. Ma'lumotlar --}}
        <section class="card p-5" x-show="time" x-cloak>
            <h2 class="font-semibold flex items-center gap-2"><span class="step">4</span>{{ __("Ma'lumotlaringiz") }}</h2>
            <div class="mt-4 grid sm:grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium mb-1">{{ __('Familiya') }} *</label><input x-model="f.last_name" class="input" autocomplete="family-name"><p class="err" x-text="err('last_name')"></p></div>
                <div><label class="block text-sm font-medium mb-1">{{ __('Ism') }} *</label><input x-model="f.first_name" class="input" autocomplete="given-name"><p class="err" x-text="err('first_name')"></p></div>
                <div><label class="block text-sm font-medium mb-1">{{ __('Otasining ismi') }}</label><input x-model="f.middle_name" class="input"></div>
                <div><label class="block text-sm font-medium mb-1">{{ __("Tug'ilgan sana") }}</label><input type="date" x-model="f.birth_date" class="input"><p class="err" x-text="err('birth_date')"></p></div>
                <div class="sm:col-span-2"><label class="block text-sm font-medium mb-1">{{ __('Telefon') }} *</label><input x-model="f.phone" class="input" placeholder="+998 90 123 45 67" inputmode="tel" autocomplete="tel"><p class="err" x-text="err('phone')"></p></div>
            </div>
            <label class="mt-4 flex items-start gap-2 text-sm text-slate-600"><input type="checkbox" x-model="f.accept" class="mt-0.5 rounded"> <span>{{ __("Men") }} <a href="{{ route('booking.offer', $clinic->slug) }}" target="_blank" class="link font-medium">{{ __("ommaviy oferta") }}</a> {{ __("shartlari bilan tanishdim, shaxsiy ma'lumotlarimni qayta ishlashga va to'lov shartlariga roziman") }}</span></label>
            <p class="err" x-text="err('accept')"></p>
            <label class="mt-3 flex items-start gap-2 text-sm text-slate-600"><input type="checkbox" x-model="f.privacy" class="mt-0.5 rounded"> <span>{{ __("Men") }} <a href="{{ route('privacy') }}" target="_blank" class="link font-medium">{{ __("maxfiylik siyosati") }}</a> {{ __("bilan tanishdim va shaxsiy hamda tibbiy ma'lumotlarimni qayta ishlashga roziman") }}</span></label>
            <p class="err" x-text="err('accept_privacy')"></p>
        </section>
    </div>

    {{-- Xulosa --}}
    <aside class="lg:sticky lg:top-24 self-start">
        <div class="card p-5">
            <div class="font-semibold flex items-center gap-2"><x-icon name="receipt" class="w-4 h-4 text-brand" />{{ __('Buyurtma') }}</div>
            <dl class="mt-4 space-y-3 text-sm">
                <div><dt class="text-slate-500">{{ __('Xizmat') }}</dt><dd class="font-medium" x-text="service ? service.name : '—'"></dd></div>
                <div><dt class="text-slate-500">{{ __('Shifokor') }}</dt><dd class="font-medium" x-text="doctor ? doctor.name : '—'"></dd></div>
                <div><dt class="text-slate-500">{{ __('Sana va vaqt') }}</dt><dd class="font-medium" x-text="date && time ? fmtDate(date) + ', ' + time : '—'"></dd></div>
            </dl>
            <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between"><span class="text-slate-500 text-sm">{{ __("To'lov summasi") }}</span><span class="text-xl font-bold text-slate-900" x-text="service ? money(service.price) : '—'"></span></div>
            <p class="err mt-2" x-text="error"></p>
            <button type="button" @click="submit()" :disabled="!canSubmit || busy" class="btn btn-primary w-full mt-4 !py-3 disabled:opacity-50 disabled:cursor-not-allowed">
                <x-icon name="credit-card" class="w-4 h-4" /><span x-text="busy ? @js(__('Kuting...')) : @js(__("To'lash va navbatga yozilish"))"></span>
            </button>
            <p class="text-xs text-slate-500 mt-3 flex items-start gap-1.5"><x-icon name="shield" class="w-3.5 h-3.5 shrink-0 mt-0.5" />{{ __("To'lov Payme orqali xavfsiz amalga oshiriladi. To'lovdan so'ng navbat raqamingiz ko'rsatiladi va SMS keladi.") }}</p>
        </div>
    </aside>
</div>
<style>.step{height:1.6rem;width:1.6rem;border-radius:9999px;display:inline-grid;place-items:center;font-size:.75rem;font-weight:700;background:#e2e8f0;color:#475569}.step.done{background:var(--brand);color:#fff}.err{color:#dc2626;font-size:.75rem;margin-top:.25rem}</style>
@endsection
@push('scripts')
<script>
function booking(services, urls) {
    return {
        services, urls, service: null, doctor: null, date: '', time: '', slots: [], loading: false, busy: false, error: '', errors: {},
        f: { last_name: '', first_name: '', middle_name: '', birth_date: '', phone: '', accept: false, privacy: false },
        money(n) { return Number(n || 0).toLocaleString('ru-RU') + " so'm"; },
        fmtDate(d) { const [y, m, dd] = d.split('-'); return dd + '.' + m + '.' + y; },
        err(k) { return (this.errors[k] || [''])[0]; },
        pickService(s) { this.service = s; this.doctor = null; this.time = ''; this.slots = []; if (s.doctors.length === 1) this.pickDoctor(s.doctors[0]); },
        pickDoctor(d) { this.doctor = d; this.time = ''; this.slots = []; if (this.date) this.loadSlots(); },
        async loadSlots() {
            this.time = ''; this.slots = []; if (!this.date || !this.doctor) return;
            this.loading = true;
            try {
                const q = new URLSearchParams({ service: this.service.id, doctor: this.doctor.id, date: this.date });
                const r = await fetch(this.urls.slots + '?' + q, { headers: { 'Accept': 'application/json' } });
                this.slots = r.ok ? (await r.json()).slots : [];
            } catch (e) { this.slots = []; }
            this.loading = false;
        },
        get canSubmit() { return this.service && this.doctor && this.date && this.time && this.f.last_name && this.f.first_name && this.f.phone && this.f.accept && this.f.privacy; },
        async submit() {
            this.busy = true; this.error = ''; this.errors = {};
            try {
                const r = await fetch(this.urls.book, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ service: this.service.id, doctor: this.doctor.id, date: this.date, time: this.time, ...this.f, accept: this.f.accept ? 1 : 0, accept_privacy: this.f.privacy ? 1 : 0 }),
                });
                const j = await r.json();
                if (r.ok && j.url) { window.location = j.url; return; }
                this.errors = j.errors || {}; this.error = j.message || '';
                if (this.errors.time) this.loadSlots();
            } catch (e) { this.error = 'Tarmoq xatosi'; }
            this.busy = false;
        },
    };
}
</script>
@endpush
