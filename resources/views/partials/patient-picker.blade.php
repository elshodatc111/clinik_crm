{{-- $patient: tanlangan bemor (yoki null) --}}
@once
<script>
function patientPicker(initial, url) {
    return {
        selected: initial, q: '', results: [],
        async search() {
            if (this.q.length < 2) { this.results = []; return; }
            const r = await fetch(url + '?q=' + encodeURIComponent(this.q), { headers: { 'Accept': 'application/json' } });
            this.results = await r.json();
        },
        pick(p) { this.selected = p; this.results = []; this.q = ''; },
        clear() { this.selected = null; }
    };
}
</script>
@endonce
<div x-data='patientPicker(@json($patient ? ["id" => $patient->id, "text" => $patient->full_name] : null), @json(route("app.patients.search")))' class="relative">
    <input type="hidden" name="patient_id" :value="selected ? selected.id : ''">
    <template x-if="selected">
        <div class="flex items-center justify-between rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm">
            <span x-text="selected.text" class="font-medium"></span>
            <button type="button" class="text-slate-500 hover:text-red-600" @click="clear()">✕</button>
        </div>
    </template>
    <div x-show="!selected">
        <input type="text" x-model="q" @input.debounce.300ms="search()" class="input" placeholder="{{ __('Familiya, ism, telefon yoki JSHSHIR') }}" autocomplete="off">
        <ul x-show="results.length" x-cloak class="absolute z-20 mt-1 w-full rounded-lg border border-slate-200 bg-white shadow-lg max-h-60 overflow-auto">
            <template x-for="p in results" :key="p.id">
                <li class="px-3 py-2 text-sm hover:bg-slate-50 cursor-pointer" @click="pick(p)" x-text="p.text"></li>
            </template>
        </ul>
    </div>
    @error('patient_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>
