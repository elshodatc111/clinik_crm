@extends('layouts.app')
@section('title', $service->exists ? __('Xizmatni tahrirlash') : __("Xizmat qo'shish"))
@section('content')
<form method="POST" action="{{ $service->exists ? route('app.services.update', $service) : route('app.services.store') }}" class="card p-6 max-w-2xl">
    @csrf
    @if($service->exists) @method('PUT') @endif
    <div class="grid md:grid-cols-2 gap-4">
        <x-field name="name" :label="__('Nomi')" :value="$service->name" required span="2" />
        <x-field name="group" :label="__('Guruh')" :value="$service->group" :placeholder="__('Masalan: Konsultatsiya')" />
        <x-field name="price" type="number" :label="__('Narx (so\'m)')" :value="$service->price" required />
        <x-field name="duration_min" type="number" :label="__('Davomiyligi (daqiqa)')" :value="$service->duration_min" required />
        <x-field name="is_active" type="checkbox" :label="__('Faol')" :value="$service->is_active" />
    </div>
    <div class="mt-6 flex gap-2"><button class="btn btn-primary">{{ __('Saqlash') }}</button><a class="btn" href="{{ route('app.services.index') }}">{{ __('Bekor qilish') }}</a></div>
</form>
@endsection
