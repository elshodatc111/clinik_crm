@extends('layouts.app')
@section('title', __('Xabarnomalar yuborish'))
@section('content')
<div class="grid lg:grid-cols-5 gap-5">
    <form method="POST" action="{{ route('admin.notifications.send') }}" class="card p-6 space-y-4 lg:col-span-3" x-data="{ aud: 'all', lvl: 'info' }">
        @csrf
        <div class="font-semibold flex items-center gap-2"><x-icon name="send" class="w-4 h-4 text-brand" />{{ __('Klinika admin va direktorlariga xabar') }}</div>
        <p class="text-sm text-slate-500">{{ __("Xabar ularning ilovasidagi qo'ng'iroqcha (bildirishnoma) bo'limida ko'rinadi.") }}</p>

        <div>
            <label class="block text-sm font-medium mb-1.5">{{ __('Kimga') }}</label>
            <div class="flex flex-wrap gap-2">
                @foreach(['all' => 'Barcha klinikalar', 'active' => 'Faol klinikalar', 'trial' => 'Demo klinikalar', 'problem' => "Muammoli (cheklangan/bloklangan)", 'selected' => 'Tanlanganlar'] as $k => $l)
                    <label class="cursor-pointer"><input type="radio" name="audience" value="{{ $k }}" x-model="aud" class="peer sr-only"><span class="inline-block px-3.5 py-1.5 rounded-lg border border-slate-200 text-sm peer-checked:bg-brand peer-checked:text-white peer-checked:border-transparent">{{ __($l) }}</span></label>
                @endforeach
            </div>
        </div>
        <div x-show="aud === 'selected'" x-cloak class="rounded-xl border border-slate-200 max-h-56 overflow-y-auto p-2 grid sm:grid-cols-2 gap-1">
            @foreach($clinics as $c)
                <label class="flex items-center gap-2 text-sm px-2 py-1 rounded hover:bg-slate-50"><input type="checkbox" name="clinics[]" value="{{ $c->id }}" class="rounded border-slate-300"><span class="truncate">{{ $c->name }}</span></label>
            @endforeach
        </div>

        <div>
            <label class="block text-sm font-medium mb-1.5">{{ __('Muhimlik') }}</label>
            <div class="flex flex-wrap gap-2">
                @foreach(['info' => 'Ma\'lumot', 'success' => 'Yaxshi xabar', 'warning' => 'Ogohlantirish', 'danger' => 'Shoshilinch'] as $k => $l)
                    <label class="cursor-pointer"><input type="radio" name="level" value="{{ $k }}" x-model="lvl" class="peer sr-only"><span class="inline-block px-3.5 py-1.5 rounded-lg border border-slate-200 text-sm peer-checked:bg-brand peer-checked:text-white peer-checked:border-transparent">{{ __($l) }}</span></label>
                @endforeach
            </div>
        </div>
        <x-field name="title" :label="__('Sarlavha')" required />
        <x-field name="body" type="textarea" rows="4" :label="__('Matn')" />
        <x-field name="url" :label="__('Havola (ixtiyoriy)')" :hint="__('Masalan: /app/billing — bosilganda shu sahifa ochiladi')" />
        <button class="btn btn-primary"><x-icon name="send" class="w-4 h-4" />{{ __('Yuborish') }}</button>
    </form>

    <div class="card overflow-hidden lg:col-span-2 self-start">
        <div class="section-title"><x-icon name="history" class="w-4 h-4 text-brand" />{{ __("Yuborilgan xabarlar") }}</div>
        <div class="divide-y divide-slate-100">
            @forelse($history as $h)
                <div class="px-5 py-3 text-sm">
                    <div class="font-medium">{{ $h->title }}</div>
                    <div class="text-xs text-slate-500 mt-0.5">{{ \Carbon\Carbon::parse($h->sent_at)->format('d.m.Y H:i') }} · {{ $h->clinics }} {{ __('klinika') }} · {{ __("O'qilgan") }}: {{ $h->readc }}/{{ $h->total }}</div>
                </div>
            @empty
                <div class="px-5 py-10 text-center text-slate-400">{{ __('Hali xabar yuborilmagan') }}</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
