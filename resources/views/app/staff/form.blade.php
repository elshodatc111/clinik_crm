@extends('layouts.app')
@section('title', $staff->exists ? __('Xodimni tahrirlash') : __("Xodim qo'shish"))
@section('content')
<form method="POST" action="{{ $staff->exists ? route('app.staff.update', $staff) : route('app.staff.store') }}" class="card p-6 max-w-3xl" x-data="{ role: '{{ old('role', $staff->role) }}', extra: @js(array_values((array) old('extra_roles', $staff->extraRoles()))) }">
    @csrf
    @if($staff->exists) @method('PUT') @endif
    <div class="grid md:grid-cols-2 gap-4">
        <x-field name="name" :label="__('F.I.Sh.')" :value="$staff->name" required />
        <x-field name="phone" :label="__('Telefon (login)')" :value="$staff->phone" required />
        <x-field name="email" type="email" :label="__('Email')" :value="$staff->email" />
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1" for="f_role">{{ __('Rol') }}</label>
            <select id="f_role" name="role" class="input" x-model="role">@foreach(\App\Models\User::clinicRoles() as $k => $l)<option value="{{ $k }}">{{ __($l) }}</option>@endforeach</select>
            @error('role')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <x-field name="password" type="password" :label="$staff->exists ? __('Yangi parol (o\'zgartirish uchun)') : __('Parol')" :required="! $staff->exists" />
        <x-field name="is_active" type="checkbox" :label="__('Faol')" :value="$staff->is_active" />
    </div>
    <div class="mt-4" x-show="role === 'clinic_admin'" x-cloak>
        <label class="block text-sm font-medium text-slate-700 mb-1" for="f_position">{{ __('Lavozim') }}</label>
        <select id="f_position" name="position" class="input max-w-xs">
            @php $pos = old('position', $staff->position ?: 'director'); @endphp
            <option value="director" @selected($pos === 'director') @disabled(! auth()->user()->isDirector() && ! $staff->isDirector())>{{ __('Direktor') }}</option>
            <option value="admin" @selected($pos === 'admin')>{{ __('Admin') }}</option>
        </select>
        <p class="mt-1 text-xs text-slate-500">{{ __("Admin va direktor bir xil huquqga ega (barcha bo'limlar). Farqi — lavozim va ba'zi amallar (masalan, tahlilni o'chirish faqat direktorda).") }}</p>
    </div>
    <div class="mt-4 rounded-xl border border-slate-200 p-4" x-show="role !== 'clinic_admin'" x-cloak>
        <div class="flex items-center gap-2 font-semibold text-slate-800 mb-1"><x-icon name="shield" class="w-4 h-4" />{{ __("Qo'shimcha ruxsatlar") }}</div>
        <p class="text-xs text-slate-500 mb-3">{{ __("Asosiy roldan tashqari, xodimga qo'shimcha rollar huquqini bering. Masalan, kassirga qabulxona ruxsati.") }}</p>
        @php $curExtra = old('extra_roles', $staff->extraRoles()); @endphp
        <div class="flex flex-wrap gap-2">
            @foreach(\App\Models\User::assignableRoles() as $k => $l)
                <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm cursor-pointer" x-show="role !== '{{ $k }}'">
                    <input type="checkbox" name="extra_roles[]" value="{{ $k }}" class="rounded border-slate-300" @checked(in_array($k, (array) $curExtra, true)) @change="extra = [...document.querySelectorAll('input[name=\'extra_roles[]\']:checked')].map(e => e.value)">{{ __($l) }}
                </label>
            @endforeach
        </div>
    </div>
    <div class="grid md:grid-cols-3 gap-4 mt-4" x-show="role === 'doctor' || (extra && extra.includes('doctor'))" x-cloak>
        <x-field name="specialty" :label="__('Mutaxassislik')" :value="$staff->specialty" />
        <x-field name="room" :label="__('Kabinet')" :value="$staff->room" />
        <x-field name="online_enabled" type="checkbox" :label="__('Onlayn qabulda ko\'rsatilsin')" :value="$staff->online_enabled" />
    </div>
    <div class="mt-6 rounded-xl border border-slate-200 p-4" x-data="{ pt: '{{ old('pay_type', $staff->pay_type ?: 'none') }}' }">
        <div class="flex items-center gap-2 font-semibold text-slate-800 mb-3"><x-icon name="banknote" class="w-4 h-4" />{{ __('Ish haqi sozlamasi') }}</div>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1" for="f_pay_type">{{ __('Ish haqi turi') }}</label>
                <select id="f_pay_type" name="pay_type" class="input" x-model="pt">@foreach(\App\Services\PayrollService::types() as $k => $l)<option value="{{ $k }}">{{ __($l) }}</option>@endforeach</select>
                @error('pay_type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div x-show="['monthly','daily','mixed'].includes(pt)" x-cloak>
                <x-field name="pay_base" type="number" :label="__('Asosiy summa (oylik yoki kunlik, so\'m)')" :value="$staff->pay_base" />
            </div>
            <div x-show="['percent','mixed'].includes(pt)" x-cloak>
                <x-field name="pay_percent" type="number" :label="__('Ulush (%)')" :value="$staff->pay_percent" attrs='step="0.01" min="0" max="100"' />
            </div>
            <div x-show="['percent','mixed'].includes(pt)" x-cloak>
                <x-field name="pay_scope" type="select" :label="__('Ulush qaysi tushumdan')" :value="$staff->pay_scope" :options="['own' => __('Faqat o\'z bemorlaridan (hisob-faktura shifokori)'), 'clinic' => __('Butun klinika tushumidan')]" />
            </div>
        </div>
        <p class="mt-2 text-xs text-slate-500">{{ __('Oylik — ishlagan kunlarga mutanosib; Kunlik — ishlagan kun × summa; Foiz — tushumdan ulush; Aralash — oylik + foiz.') }}</p>
    </div>
    <div class="mt-6 flex gap-2"><button class="btn btn-primary">{{ __('Saqlash') }}</button><a class="btn" href="{{ route('app.staff.index') }}">{{ __('Bekor qilish') }}</a></div>
</form>
@endsection
