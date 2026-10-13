@extends('layouts.app')
@section('title', __("Foydalanish yo'riqnomasi"))
@section('content')
<div class="hero-grad rounded-2xl p-6 md:p-8 text-white mb-6 flex flex-wrap items-center gap-6">
    <div class="flex-1 min-w-[240px]">
        <h2 class="text-2xl font-bold">{{ __("Foydalanish yo'riqnomasi") }}</h2>
        <p class="mt-2 text-white/80 max-w-xl">{{ __("Har bir lavozim uchun qadam-baqadam qo'llanma. Istalganini ochib o'qing yoki PDF qilib yuklab oling.") }}</p>
    </div>
    <div class="w-44 h-32 opacity-95"><x-illu name="team" /></div>
</div>
<div class="grid md:grid-cols-2 xl:grid-cols-3 gap-5">
    @foreach($guides as $key => $g)
        <a href="{{ route('app.guides.show', $key) }}" class="card p-5 group transition hover:-translate-y-0.5 hover:shadow-lg">
            <div class="h-36 rounded-xl bg-brand-soft p-2 mb-4"><x-illu :name="$g['illu']" /></div>
            <div class="flex items-center gap-2 font-semibold text-slate-900"><span class="h-8 w-8 rounded-lg bg-blue-50 text-blue-600 grid place-items-center"><x-icon :name="$g['icon']" class="w-4 h-4" /></span>{{ \App\Support\Guides::text($g['title']) }}</div>
            <p class="mt-2 text-sm text-slate-500 line-clamp-3">{{ \App\Support\Guides::text($g['intro']) }}</p>
            <div class="mt-3 text-xs text-slate-400">{{ count($g['sections']) }} {{ __("bo'lim") }}</div>
            <div class="mt-3 text-sm font-medium text-brand inline-flex items-center gap-1">{{ __("O'qish") }} <x-icon name="chevron-right" class="w-4 h-4 transition group-hover:translate-x-0.5" /></div>
        </a>
    @endforeach
</div>
@endsection
