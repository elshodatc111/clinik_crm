{{-- Tizimdan chiqishni tasdiqlash oynasi --}}
<div x-data="{ open: false, busy: false }" @confirm-logout.window="open = true" @keydown.escape.window="open = false" x-show="open" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center p-4" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="open = false"></div>
    <div class="relative card shadow-2xl p-6 w-full max-w-sm text-center" x-transition>
        <span class="mx-auto h-14 w-14 rounded-2xl bg-red-50 text-red-600 grid place-items-center"><x-icon name="logout" class="w-7 h-7" /></span>
        <h3 class="mt-4 text-lg font-bold">{{ __('Tizimdan chiqasizmi?') }}</h3>
        <p class="mt-1.5 text-sm text-slate-500">{{ __("Chiqqaningizdan so'ng qayta kirish uchun login va parol kerak bo'ladi.") }}</p>
        <div class="mt-6 grid grid-cols-2 gap-3">
            <button type="button" @click="open = false" class="btn !py-2.5">{{ __('Bekor qilish') }}</button>
            <form method="POST" action="{{ route('logout') }}" @submit="busy = true">@csrf
                <button class="btn btn-danger w-full !py-2.5" :disabled="busy"><x-icon name="logout" class="w-4 h-4" />{{ __('Ha, chiqish') }}</button>
            </form>
        </div>
    </div>
</div>
