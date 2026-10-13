@extends('layouts.app')
@section('title', $patient->exists ? __('Bemorni tahrirlash') : __('Yangi bemor'))
@section('content')
<form method="POST" action="{{ $patient->exists ? route('app.patients.update', $patient) : route('app.patients.store') }}" class="card p-6 max-w-4xl">
    @csrf
    @if($patient->exists) @method('PUT') @endif
    <div class="grid md:grid-cols-3 gap-4">
        <x-field name="last_name" :label="__('Familiya')" :value="$patient->last_name" required />
        <x-field name="first_name" :label="__('Ism')" :value="$patient->first_name" required />
        <x-field name="middle_name" :label="__('Otasining ismi')" :value="$patient->middle_name" />
        <div x-data="pinflLookup(@js(route('app.patients.lookup')), @json(\App\Services\PinflLookupService::enabled($clinic) && ! $patient->exists))">
            <x-field name="pinfl" :label="__('JSHSHIR (14 raqam)')" :value="$patient->pinfl" attrs='inputmode="numeric" maxlength="14" x-ref="pinfl" @input="onInput($event)"' :hint="__('Bitta bemor klinikada faqat bir marta kiritiladi')" />
            <template x-if="enabled">
                <div class="mt-1.5 text-xs">
                    <button type="button" class="link inline-flex items-center gap-1" @click="run()" :disabled="busy"><svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>{{ __("JSHSHIR bo'yicha to'ldirish") }}</button>
                    <span x-show="busy" class="text-slate-500 ml-1">{{ __('Kuting...') }}</span>
                    <span x-show="msg" :class="ok ? 'text-emerald-600' : 'text-red-600'" class="ml-1" x-text="msg"></span>
                    <a x-show="link" :href="link" class="link ml-1">{{ __("Ko'rish") }}</a>
                </div>
            </template>
        </div>
        <x-field name="birth_date" type="date" :label="__('Tug\'ilgan sana')" :value="$patient->birth_date?->format('Y-m-d')" />
        <x-field name="gender" type="select" :label="__('Jinsi')" :value="$patient->gender" :options="['' => '—', 'm' => __('Erkak'), 'f' => __('Ayol')]" />
        <x-field name="phone" :label="__('Telefon')" :value="$patient->phone" placeholder="+998 90 123 45 67" />
        <x-field name="phone2" :label="__('Qo\'shimcha telefon')" :value="$patient->phone2" />
        <x-field name="blood_group" type="select" :label="__('Qon guruhi')" :value="$patient->blood_group" :options="['' => '—', 'O(I) Rh+' => 'O(I) Rh+', 'O(I) Rh-' => 'O(I) Rh-', 'A(II) Rh+' => 'A(II) Rh+', 'A(II) Rh-' => 'A(II) Rh-', 'B(III) Rh+' => 'B(III) Rh+', 'B(III) Rh-' => 'B(III) Rh-', 'AB(IV) Rh+' => 'AB(IV) Rh+', 'AB(IV) Rh-' => 'AB(IV) Rh-']" />
        <x-field name="address" :label="__('Manzil')" :value="$patient->address" span="2" />
        <x-field name="source" type="select" :label="__('Qayerdan bildi')" :value="$patient->source" :options="['' => '—', 'Instagram' => 'Instagram', 'Telegram' => 'Telegram', 'Facebook' => 'Facebook', 'Google' => 'Google', 'Tanish-bilish' => __('Tanish-bilish'), 'Reklama' => __('Reklama'), 'Boshqa' => __('Boshqa')]" />
        <x-field name="allergies" type="textarea" :label="__('Allergiya')" :value="$patient->allergies" rows="2" span="2" />
        <x-field name="chronic_diseases" type="textarea" :label="__('Surunkali kasalliklar')" :value="$patient->chronic_diseases" rows="2" span="2" />
        <x-field name="notes" type="textarea" :label="__('Izoh')" :value="$patient->notes" rows="2" span="2" />
    </div>
    <div class="mt-6 flex gap-2"><button class="btn btn-primary">{{ __('Saqlash') }}</button><a class="btn" href="{{ $patient->exists ? route('app.patients.show', $patient) : route('app.patients.index') }}">{{ __('Bekor qilish') }}</a></div>
</form>
@endsection
@push('scripts')
<script>
function pinflLookup(url, enabled) {
    return {
        enabled, busy: false, msg: '', ok: false, link: '',
        onInput(e) { if (this.enabled && /^\d{14}$/.test(e.target.value)) this.run(); },
        async run() {
            const f = this.$refs.pinfl; const v = (f.value || '').trim();
            this.msg = ''; this.link = ''; if (!/^\d{14}$/.test(v)) { this.ok = false; this.msg = @js(__("JSHSHIR 14 ta raqamdan iborat bo'lishi kerak.")); return; }
            this.busy = true;
            try {
                const r = await fetch(url + '?pinfl=' + v, { headers: { 'Accept': 'application/json' } });
                const j = await r.json();
                if (j.status === 'ok') {
                    const set = (n, val) => { const el = document.querySelector('[name=' + n + ']'); if (el && val) el.value = val; };
                    ['last_name', 'first_name', 'middle_name', 'birth_date', 'gender', 'address'].forEach(k => set(k, j.data[k]));
                    this.ok = true; this.msg = @js(__("Ma'lumotlar to'ldirildi."));
                } else { this.ok = false; this.msg = j.message || ''; this.link = j.url || ''; }
            } catch (e) { this.ok = false; this.msg = @js(__('Tarmoq xatosi')); }
            this.busy = false;
        },
    };
}
</script>
@endpush
