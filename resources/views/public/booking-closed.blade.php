@extends('layouts.booking')
@section('title', __('Onlayn qabul'))
@section('content')
<div class="card p-10 text-center max-w-lg mx-auto">
    <span class="mx-auto h-14 w-14 rounded-2xl bg-amber-50 text-amber-600 grid place-items-center"><x-icon name="clock" class="w-7 h-7" /></span>
    <h1 class="mt-4 text-xl font-bold text-slate-900">{{ __('Onlayn qabul hozircha mavjud emas') }}</h1>
    <p class="mt-2 text-slate-500">{{ __("Iltimos, klinikaga telefon orqali murojaat qiling.") }}</p>
    @if($clinic->phone)<a href="tel:{{ $clinic->phone }}" class="btn btn-primary mt-5"><x-icon name="phone" class="w-4 h-4" />{{ phone_fmt($clinic->phone) }}</a>@endif
</div>
@endsection
