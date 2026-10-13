@extends('layouts.public')
@section('title', __('Ommaviy oferta'))
@section('content')
<div class="max-w-3xl mx-auto px-4 py-12">
    <a href="{{ route('booking.show', $clinic->slug) }}" class="link text-sm">&larr; {{ $clinic->name }}</a>
    <h1 class="text-3xl font-bold mt-3">{{ __('Ommaviy oferta') }}</h1>
    <p class="text-sm text-slate-500 mt-1">{{ __('Onlayn qabulga yozilish shartlari') }} · {{ $clinic->name }}</p>
    <div class="card p-6 mt-6 whitespace-pre-line leading-relaxed text-slate-700">{{ $text }}</div>
</div>
@endsection
