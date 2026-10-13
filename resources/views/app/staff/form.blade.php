@extends('layouts.app')
@section('title', $staff->exists ? __('Xodimni tahrirlash') : __("Xodim qo'shish"))
@section('content')
<form method="POST" action="{{ $staff->exists ? route('app.staff.update', $staff) : route('app.staff.store') }}" class="card p-6 max-w-3xl" x-data="{ role: '{{ old('role', $staff->role) }}' }">
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
    <div class="grid md:grid-cols-3 gap-4 mt-4" x-show="role === 'doctor'" x-cloak>
        <x-field name="specialty" :label="__('Mutaxassislik')" :value="$staff->specialty" />
        <x-field name="room" :label="__('Kabinet')" :value="$staff->room" />
        <x-field name="percent" type="number" :label="__('Xizmatdan ulush (%)')" :value="$staff->percent" />
    </div>
    <div class="mt-6 flex gap-2"><button class="btn btn-primary">{{ __('Saqlash') }}</button><a class="btn" href="{{ route('app.staff.index') }}">{{ __('Bekor qilish') }}</a></div>
</form>
@endsection
