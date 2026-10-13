@extends('layouts.app')
@section('title', __('Mening profilim'))
@section('content')
<div class="grid lg:grid-cols-3 gap-5 max-w-6xl">

    {{-- Chap: shaxsiy karta --}}
    <div class="card overflow-hidden self-start">
        <div class="hero-grad h-24"></div>
        <div class="px-6 pb-6 -mt-12 text-center">
            <div class="mx-auto h-24 w-24 rounded-full ring-4 ring-white bg-brand text-white grid place-items-center text-3xl font-bold overflow-hidden shadow-lg">
                @if($u->avatarUrl())<img src="{{ $u->avatarUrl() }}" class="h-full w-full object-cover" alt="">@else{{ $u->initial() }}@endif
            </div>
            <div class="mt-3 text-lg font-bold">{{ $u->name }}</div>
            <div class="mt-1 flex flex-wrap justify-center gap-1.5">
                @if($u->isSuper())
                    <x-badge color="blue">Super Admin</x-badge>
                @else
                    @foreach($u->roleLabels() as $rl)<x-badge color="blue">{{ __($rl) }}</x-badge>@endforeach
                @endif
            </div>
            <dl class="mt-5 text-sm text-left space-y-2.5 border-t border-slate-100 pt-4">
                @if($clinic)<div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Klinika') }}</dt><dd class="font-medium text-right">{{ $clinic->name }}</dd></div>@endif
                <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Telefon') }}</dt><dd class="font-medium">{{ phone_fmt($u->phone) }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __("Oxirgi kirish") }}</dt><dd class="font-medium">{{ $u->last_login_at?->format('d.m.Y H:i') ?? '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __("Ro'yxatdan o'tgan") }}</dt><dd class="font-medium">{{ $u->created_at?->format('d.m.Y') }}</dd></div>
            </dl>
            @if($u->avatarUrl())
                <form method="POST" action="{{ route('account.avatar.delete') }}" class="mt-4">@csrf @method('DELETE')<button class="text-xs text-slate-500 hover:text-red-600">{{ __('Rasmni olib tashlash') }}</button></form>
            @endif
        </div>
    </div>

    <div class="lg:col-span-2 space-y-5">
        {{-- Ma'lumotlar --}}
        <form method="POST" action="{{ route('account.update') }}" enctype="multipart/form-data" class="card p-6 space-y-4" x-data="{ pass: false }">
            @csrf @method('PUT')
            <div class="font-semibold flex items-center gap-2"><x-icon name="user" class="w-4 h-4 text-brand" />{{ __("Shaxsiy ma'lumotlar") }}</div>
            <div class="grid md:grid-cols-2 gap-4">
                <x-field name="name" :label="__('F.I.Sh.')" :value="$u->name" required />
                <x-field name="phone" :label="__('Telefon (login)')" :value="$u->phone" required />
                <x-field name="email" type="email" :label="__('Email (login, ixtiyoriy)')" :value="$u->email" />
                <x-field name="locale" type="select" :label="__('Interfeys tili')" :value="$u->locale" :options="['uz' => 'Oʻzbekcha', 'ru' => 'Русский']" />
                @if($u->hasRole('doctor'))
                    <x-field name="specialty" :label="__('Mutaxassislik')" :value="$u->specialty" />
                    <x-field name="room" :label="__('Xona')" :value="$u->room" />
                @endif
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Profil rasmi') }}</label>
                    <input type="file" name="avatar" accept="image/*" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm">
                    @error('avatar')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <x-field name="current_password" type="password" :label="__('Joriy parol')" :hint="__('Faqat telefon yoki emailni o\'zgartirsangiz kerak bo\'ladi')" />
                </div>
            </div>
            <button class="btn btn-primary"><x-icon name="check" class="w-4 h-4" />{{ __('Saqlash') }}</button>
        </form>

        {{-- Parolni yangilash --}}
        <form method="POST" action="{{ route('account.password') }}" class="card p-6 space-y-4" id="password"
              x-data="{ show: false, p: '', get score() { let s = 0; if (this.p.length >= 8) s++; if (/[A-Z]/.test(this.p) && /[a-z]/.test(this.p)) s++; if (/\d/.test(this.p)) s++; if (/[^A-Za-z0-9]/.test(this.p) || this.p.length >= 12) s++; return s; } }">
            @csrf @method('PUT')
            <div class="flex items-center justify-between">
                <div class="font-semibold flex items-center gap-2"><x-icon name="lock" class="w-4 h-4 text-brand" />{{ __('Parolni yangilash') }}</div>
                <button type="button" @click="show = !show" class="text-xs link" x-text="show ? @js(__('Yashirish')) : @js(__('Ko\'rsatish'))"></button>
            </div>
            @if($errors->password->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><ul class="list-disc pl-5">@foreach($errors->password->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif
            <div class="grid md:grid-cols-2 gap-4">
                <div class="md:col-span-2"><label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Joriy parol') }}</label><input :type="show ? 'text' : 'password'" name="current_password" class="input" autocomplete="current-password" required></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Yangi parol') }}</label><input :type="show ? 'text' : 'password'" name="password" x-model="p" class="input" autocomplete="new-password" minlength="8" required></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1">{{ __('Yangi parolni takrorlang') }}</label><input :type="show ? 'text' : 'password'" name="password_confirmation" class="input" autocomplete="new-password" required></div>
            </div>
            <div>
                <div class="flex gap-1.5"><template x-for="i in 4" :key="i"><span class="h-1.5 flex-1 rounded-full transition-colors" :class="i <= score ? (score <= 1 ? 'bg-red-500' : score === 2 ? 'bg-amber-500' : 'bg-emerald-500') : 'bg-slate-200'"></span></template></div>
                <p class="text-xs text-slate-500 mt-1.5">{{ __("Kamida 8 ta belgi. Katta-kichik harf, raqam va belgi aralashmasi xavfsizroq.") }}</p>
            </div>
            <button class="btn btn-primary"><x-icon name="lock" class="w-4 h-4" />{{ __('Parolni yangilash') }}</button>
        </form>
    </div>
</div>
@endsection
