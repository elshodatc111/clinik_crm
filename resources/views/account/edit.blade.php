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

        {{-- Ikki bosqichli tasdiqlash --}}
        @php($tf = \App\Http\Controllers\TwoFactorController::setupData(request()))
        <div class="card p-6 space-y-4" id="twofactor">
            <div class="flex items-center justify-between gap-3">
                <div class="font-semibold flex items-center gap-2"><x-icon name="shield" class="w-4 h-4 text-brand" />{{ __('Ikki bosqichli tasdiqlash (2FA)') }}</div>
                @if($u->hasTwoFactor())<x-badge color="green">{{ __('Yoqilgan') }}</x-badge>@else<x-badge color="amber">{{ __("O'chiq") }}</x-badge>@endif
            </div>
            <p class="text-sm text-slate-500">{{ __("Kirishda paroldan tashqari autentifikator ilovasidagi (Google Authenticator, Authy va h.k.) 6 xonali kod so'raladi. Parol o'g'irlansa ham akkaunt himoyalangan bo'ladi.") }}@if($u->isSuper() || $u->isDirector()) <b>{{ __('Direktor va Super Admin uchun qat\'iy tavsiya etiladi.') }}</b>@endif</p>
            @if($errors->twofactor->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><ul class="list-disc pl-5">@foreach($errors->twofactor->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif
            @if(session('recovery'))
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                    <div class="text-sm font-semibold text-amber-900 mb-2">{{ __('Zaxira kodlar (har biri bir marta ishlaydi). Hozir saqlab oling — qayta ko\'rsatilmaydi:') }}</div>
                    <div class="grid grid-cols-2 gap-1 font-mono text-sm">@foreach(session('recovery') as $rc)<span>{{ $rc }}</span>@endforeach</div>
                </div>
            @endif
            @if($u->hasTwoFactor())
                <div class="grid md:grid-cols-2 gap-4">
                    <form method="POST" action="{{ route('account.2fa.recovery') }}" class="space-y-2">@csrf
                        <label class="block text-sm font-medium">{{ __('Yangi zaxira kodlar') }}</label>
                        <input type="password" name="password" class="input" placeholder="{{ __('Joriy parol') }}" required>
                        <button class="btn btn-sm">{{ __('Kodlarni yangilash') }}</button>
                    </form>
                    <form method="POST" action="{{ route('account.2fa.disable') }}" class="space-y-2">@csrf
                        <label class="block text-sm font-medium">{{ __("2FA ni o'chirish") }}</label>
                        <input type="password" name="password" class="input" placeholder="{{ __('Joriy parol') }}" required>
                        <button class="btn btn-sm !text-red-600">{{ __("O'chirish") }}</button>
                    </form>
                </div>
            @elseif($tf)
                <div class="grid sm:grid-cols-[180px_1fr] gap-5 items-start">
                    <div id="qr2fa" class="h-44 w-44 bg-white p-2 rounded-lg border border-slate-200 grid place-items-center text-xs text-slate-400"></div>
                    <div class="space-y-3 text-sm">
                        <div>{{ __('1. Autentifikator ilovasida QR kodni skanerlang yoki kalitni qo\'lda kiriting:') }}</div>
                        <div class="font-mono text-sm break-all rounded bg-slate-100 px-3 py-2 select-all">{{ $tf['secret'] }}</div>
                        <form method="POST" action="{{ route('account.2fa.confirm') }}" class="space-y-2">@csrf
                            <label class="block font-medium">{{ __('2. Ilovadagi 6 xonali kodni kiriting:') }}</label>
                            <input name="code" class="input max-w-[12rem]" inputmode="numeric" autocomplete="one-time-code" maxlength="10" required>
                            <div><button class="btn btn-primary btn-sm">{{ __('Tasdiqlash va yoqish') }}</button></div>
                        </form>
                    </div>
                </div>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
                <script>
                    (function () {
                        try { var q = qrcode(0, 'M'); q.addData(@js($tf['uri'])); q.make(); document.getElementById('qr2fa').innerHTML = q.createImgTag(4, 0); } catch (e) {}
                    })();
                </script>
            @else
                <form method="POST" action="{{ route('account.2fa.start') }}">@csrf<button class="btn btn-primary btn-sm"><x-icon name="shield" class="w-4 h-4" />{{ __('Yoqish') }}</button></form>
            @endif
        </div>
    </div>
</div>
@endsection
