@extends('layouts.public')
@section('title', __('Ommaviy oferta'))
@section('content')
<div class="max-w-3xl mx-auto px-4 py-12">
    <h1 class="text-3xl font-bold">{{ $offer->title ?? __('Ommaviy oferta') }}</h1>
    @if($offer)<p class="text-sm text-slate-500 mt-1">{{ __('Versiya') }} {{ $offer->version }}</p>@endif
    <div class="mt-6 whitespace-pre-line leading-relaxed text-slate-700">{{ $offer->body ?? __('Oferta hali kiritilmagan.') }}</div>
</div>
@endsection
