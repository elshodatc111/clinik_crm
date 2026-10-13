<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\ClinicStatusLog;
use App\Models\Offer;
use App\Models\OfferAcceptance;
use App\Models\Setting;
use App\Models\User;
use App\Services\BillingService;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function loginForm()
    {
        return view('auth.login');
    }

    public function login(Request $r)
    {
        $d = $r->validate(['login' => 'required|string|max:150', 'password' => 'required|string']);
        $login = trim($d['login']);
        $key = 'login:'.Str::lower($login).'|'.$r->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['login' => __("Juda ko'p urinish. :s soniyadan keyin qayta urinib ko'ring.", ['s' => RateLimiter::availableIn($key)])])->onlyInput('login');
        }

        $user = User::where('email', Str::lower($login))
            ->orWhere('phone', SmsService::normalize($login))->first();

        if (! $user || ! Hash::check($d['password'], $user->password) || ! $user->is_active) {
            RateLimiter::hit($key, 60);

            return back()->withErrors(['login' => __("Login yoki parol noto'g'ri.")])->onlyInput('login');
        }

        RateLimiter::clear($key);
        Auth::login($user, $r->boolean('remember'));
        $r->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();
        session(['locale' => $user->locale]);

        return redirect()->route('dashboard');
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect()->route('landing');
    }

    public function home(Request $r)
    {
        return redirect()->route($r->user()->isSuper() ? 'admin.dashboard' : 'app.dashboard');
    }

    // ---------- Ro'yxatdan o'tish (14 kunlik demo) ----------

    public function registerForm()
    {
        return view('auth.register', ['offer' => Offer::active(), 'demoDays' => (int) Setting::get('demo_days', 14)]);
    }

    public function register(Request $r)
    {
        $offer = Offer::active();
        abort_unless($offer, 503, "Oferta kiritilmagan. Super Admin ofertani kiritishi kerak.");

        $d = $r->validate([
            'clinic_name' => 'required|string|max:150',
            'owner_name' => 'required|string|max:150',
            'phone' => ['required', 'string', 'regex:/^\+?[0-9\s\-\(\)]{9,20}$/'],
            'email' => 'nullable|email|max:150|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'accept_offer' => 'accepted',
        ]);

        $key = 'register:'.$r->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return back()->withErrors(['phone' => __("Juda ko'p urinish. Keyinroq qayta urinib ko'ring.")])->onlyInput('clinic_name', 'owner_name', 'phone', 'email');
        }
        RateLimiter::hit($key, 3600);

        $phone = SmsService::normalize($d['phone']);
        if (strlen($phone) !== 12 || ! str_starts_with($phone, '998')) {
            return back()->withErrors(['phone' => __("Telefon raqami +998 XX XXX XX XX ko'rinishida bo'lishi kerak.")])->onlyInput('clinic_name', 'owner_name', 'phone', 'email');
        }
        if (User::where('phone', $phone)->exists() || Clinic::where('phone', $phone)->exists()) {
            return back()->withErrors(['phone' => __("Bu raqam bilan allaqachon ro'yxatdan o'tilgan.")])->onlyInput('clinic_name', 'owner_name', 'phone', 'email');
        }

        session(['reg' => [
            'clinic_name' => $d['clinic_name'],
            'owner_name' => $d['owner_name'],
            'phone' => $phone,
            'email' => $d['email'] ? Str::lower($d['email']) : null,
            'password' => Hash::make($d['password']),
            'offer_id' => $offer->id,
        ]]);
        $this->sendCode($phone);

        return redirect()->route('register.verify');
    }

    public function verifyForm()
    {
        if (! session('reg')) {
            return redirect()->route('register');
        }

        return view('auth.verify', ['phone' => session('reg.phone')]);
    }

    public function resend()
    {
        $reg = session('reg');
        if (! $reg) {
            return redirect()->route('register');
        }
        $this->sendCode($reg['phone']);

        return back()->with('success', __('Kod qayta yuborildi.'));
    }

    public function verify(Request $r)
    {
        $reg = session('reg');
        if (! $reg) {
            return redirect()->route('register');
        }
        $r->validate(['code' => 'required|digits:6']);

        $row = DB::table('sms_codes')->where('phone', $reg['phone'])->first();
        if (! $row || now()->greaterThan(Carbon::parse($row->expires_at))) {
            return back()->withErrors(['code' => __('Kod muddati tugagan. Qayta yuboring.')]);
        }
        if ($row->attempts >= 5) {
            return back()->withErrors(['code' => __("Urinishlar soni oshdi. Kodni qayta yuboring.")]);
        }
        DB::table('sms_codes')->where('id', $row->id)->increment('attempts');
        if (! Hash::check($r->input('code'), $row->code)) {
            return back()->withErrors(['code' => __("Kod noto'g'ri.")]);
        }

        $offer = Offer::find($reg['offer_id']);
        $user = DB::transaction(function () use ($reg, $offer, $r) {
            $clinic = Clinic::create([
                'name' => $reg['clinic_name'],
                'owner_name' => $reg['owner_name'],
                'phone' => $reg['phone'],
                'email' => $reg['email'],
                'status' => Clinic::TRIAL,
                'trial_ends_at' => now()->addDays((int) Setting::get('demo_days', 14)),
                'settings' => [],
            ]);
            ClinicStatusLog::create(['clinic_id' => $clinic->id, 'to_status' => Clinic::TRIAL, 'reason' => "Ro'yxatdan o'tdi"]);
            $user = User::create([
                'clinic_id' => $clinic->id,
                'name' => $reg['owner_name'],
                'email' => $reg['email'],
                'phone' => $reg['phone'],
                'password' => $reg['password'],
                'role' => User::ADMIN,
                'phone_verified_at' => now(),
                'locale' => session('locale', 'uz'),
            ]);
            OfferAcceptance::create([
                'clinic_id' => $clinic->id,
                'clinic_name' => $clinic->name,
                'user_name' => $user->name,
                'phone' => $clinic->phone,
                'offer_id' => $offer->id,
                'offer_version' => $offer->version,
                'ip_address' => $r->ip(),
                'user_agent' => mb_substr((string) $r->userAgent(), 0, 500),
                'accepted_at' => now(),
            ]);

            return $user;
        });

        DB::table('sms_codes')->where('phone', $reg['phone'])->delete();
        session()->forget('reg');
        Auth::login($user);
        $r->session()->regenerate();

        return redirect()->route('app.profile.edit')->with('success', __("Xush kelibsiz! Demo davringiz boshlandi. Avval klinika ma'lumotlarini to'ldiring."));
    }

    protected function sendCode(string $phone): void
    {
        DB::table('sms_codes')->where('phone', $phone)->delete();
        $code = (string) random_int(100000, 999999);
        DB::table('sms_codes')->insert([
            'phone' => $phone, 'code' => Hash::make($code), 'expires_at' => now()->addMinutes(5),
            'attempts' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $sent = SmsService::send($phone, 'Tasdiqlash kodi: '.$code.'. '.BillingService::appName());
        if (! $sent && config('app.debug')) {
            session()->flash('dev_code', $code); // faqat test rejimida (APP_DEBUG=true va Eskiz sozlanmagan)
        }
    }
}
