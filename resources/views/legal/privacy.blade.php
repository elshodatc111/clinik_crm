@extends('layouts.public')
@section('title', __('Maxfiylik siyosati'))
@section('content')
<div class="max-w-3xl mx-auto px-4 py-12">
    <h1 class="text-3xl font-bold">{{ __('Maxfiylik siyosati') }}</h1>
    <div class="mt-6 whitespace-pre-line leading-relaxed text-slate-700">{{ $text }}</div>
</div>
@endsection
