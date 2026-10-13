@extends('layouts.public')
@section('title', __('Ommaviy oferta'))
@section('content')
<div class="max-w-3xl mx-auto px-4 py-10" x-data="{ ok: false }">
    <div class="rounded-xl border border-amber-200 bg-amber-50 text-amber-900 px-4 py-3 text-sm flex gap-2">
        <x-icon name="shield" class="w-5 h-5 shrink-0" />
        <div>
            @if($reaccept)
                {{ __('Ommaviy oferta yangilandi. Ishni davom ettirish uchun yangi versiya bilan tanishib, rozilik bering.') }}
            @else
                {{ __('Tizimdan foydalanish uchun ommaviy oferta shartlari bilan tanishib, rozilik bildirishingiz shart.') }}
            @endif
        </div>
    </div>
    <h1 class="text-2xl font-bold mt-5">{{ $offer->title }}</h1>
    <p class="text-sm text-slate-500 mt-1">{{ __('Versiya') }} {{ $offer->version }} · {{ $offer->created_at?->format('d.m.Y') }}</p>
    <div class="card mt-4 p-5 h-[55vh] overflow-y-auto whitespace-pre-line leading-relaxed text-sm text-slate-700" tabindex="0">{{ $text }}</div>
    <p class="mt-2 text-xs text-slate-500"><a href="{{ route('privacy') }}" target="_blank" class="link">{{ __('Maxfiylik siyosati') }}</a></p>

    <form method="POST" action="{{ route('offer.accept.post') }}" class="mt-4 space-y-3">
        @csrf
        <input type="hidden" name="offer_id" value="{{ $offer->id }}">
        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" name="accept_offer" value="1" x-model="ok" class="mt-0.5 rounded border-slate-300">
            <span>{{ __("Men ommaviy oferta va maxfiylik siyosati bilan tanishdim, shartlarini to'liq qabul qilaman") }}</span>
        </label>
        @error('accept_offer')<p class="text-xs text-red-600">{{ __('Davom etish uchun ofertaga rozilik bering.') }}</p>@enderror
        <div class="flex flex-wrap items-center gap-3">
            <button class="btn btn-primary" :disabled="!ok" :class="!ok && 'opacity-50 cursor-not-allowed'"><x-icon name="check" class="w-4 h-4" />{{ __('Roziman va davom etaman') }}</button>
            <button type="submit" form="logout-form-offer" class="btn">{{ __('Tizimdan chiqish') }}</button>
        </div>
    </form>
    <form id="logout-form-offer" method="POST" action="{{ route('logout') }}">@csrf</form>
</div>
@endsection
