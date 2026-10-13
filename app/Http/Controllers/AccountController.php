<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Har bir foydalanuvchining shaxsiy profili: ma'lumotlar, rasm, parolni yangilash. */
class AccountController extends Controller
{
    public function edit(Request $r)
    {
        $u = $r->user();

        return view('account.edit', ['u' => $u, 'clinic' => $u->clinic_id ? $u->clinic : null]);
    }

    public function update(Request $r)
    {
        $u = $r->user();
        $d = $r->validate([
            'name' => 'required|string|max:150',
            'phone' => ['required', 'string', 'regex:/^\+?[0-9\s\-\(\)]{9,20}$/'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($u->id)],
            'locale' => 'required|in:uz,ru',
            'specialty' => 'nullable|string|max:150',
            'room' => 'nullable|string|max:50',
            'avatar' => 'nullable|image|max:2048',
            'current_password' => 'nullable|string',
        ]);

        $phone = SmsService::normalize($d['phone']);
        $email = ($d['email'] ?? null) ? mb_strtolower($d['email']) : null;
        if (User::where('phone', $phone)->where('id', '!=', $u->id)->exists()) {
            return back()->withInput()->withErrors(['phone' => __('Bu telefon raqami boshqa xodimda ishlatilgan.')]);
        }
        // Kirish ma'lumotlari (telefon/email) o'zgarsa, joriy parol talab qilinadi
        if (($phone !== $u->phone || $email !== $u->email) && ! Hash::check((string) ($d['current_password'] ?? ''), $u->password)) {
            return back()->withInput()->withErrors(['current_password' => __("Telefon yoki emailni o'zgartirish uchun joriy parolni kiriting.")]);
        }

        $upd = ['name' => $d['name'], 'phone' => $phone, 'email' => $email, 'locale' => $d['locale']];
        if ($u->role === User::DOCTOR || $u->hasRole(User::DOCTOR)) {
            $upd['specialty'] = $d['specialty'] ?? null;
            $upd['room'] = $d['room'] ?? null;
        }
        if ($r->hasFile('avatar')) {
            if ($u->avatar_path) {
                Storage::disk('public')->delete($u->avatar_path);
            }
            $upd['avatar_path'] = $r->file('avatar')->store('avatars', 'public');
        }
        $u->update($upd);
        session(['locale' => $d['locale']]);
        app()->setLocale($d['locale']);

        return redirect()->route('account.edit')->with('success', __('Profil saqlandi.'));
    }

    public function password(Request $r)
    {
        $u = $r->user();
        $key = 'pwchange:'.$u->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['current_password' => __("Juda ko'p urinish. :s soniyadan keyin qayta urinib ko'ring.", ['s' => RateLimiter::availableIn($key)])], 'password');
        }
        $d = $r->validateWithBag('password', [
            'current_password' => 'required|string',
            'password' => ['required', 'string', 'max:100', 'confirmed', 'different:current_password', new \App\Rules\StrongPassword],
        ]);
        if (! Hash::check($d['current_password'], $u->password)) {
            RateLimiter::hit($key, 60);

            return back()->withErrors(['current_password' => __("Joriy parol noto'g'ri.")], 'password');
        }
        RateLimiter::clear($key);
        $u->update(['password' => $d['password']]);
        $r->session()->regenerate();

        return redirect()->route('account.edit')->with('success', __('Parol yangilandi.'));
    }

    public function removeAvatar(Request $r)
    {
        $u = $r->user();
        if ($u->avatar_path) {
            Storage::disk('public')->delete($u->avatar_path);
            $u->update(['avatar_path' => null]);
        }

        return back()->with('success', __('Rasm olib tashlandi.'));
    }
}
