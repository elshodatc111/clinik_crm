<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Clinic;
use App\Models\ClinicStatusLog;
use App\Models\Offer;
use App\Models\OfferAcceptance;
use App\Models\Setting;
use App\Models\User;
use App\Services\BillingService;
use App\Services\PlatformSms;
use App\Services\SmsService;
use App\Services\OtpCodes;
use App\Services\TelegramOtp;
use App\Services\Totp;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
        $d = $r->validate(['login' => 'required|string|max:150', 'password' => 'required|string|max:200']);
        $login = trim($d['login']);
        $key = 'login:'.Str::lower($login).'|'.$r->ip();
        $ipKey = 'login-ip:'.$r->ip();

        if (RateLimiter::tooManyAttempts($key, 5) || RateLimiter::tooManyAttempts($ipKey, 30)) {
            $s = max(RateLimiter::availableIn($key), RateLimiter::availableIn($ipKey));

            return back()->withErrors(['login' => __("Juda ko'p urinish. :s soniyadan keyin qayta urinib ko'ring.", ['s' => $s])])->onlyInput('login');
        }

        $user = User::where('email', Str::lower($login))
            ->orWhere('phone', SmsService::normalize($login))->first();

        if (! $user || ! Hash::check($d['password'], $user->password) || ! $user->is_active) {
            RateLimiter::hit($key, 60);
            RateLimiter::hit($ipKey, 60);
            AuditLog::record('login_failed', $user?->id, $user?->clinic_id, ['login' => mb_substr($login, 0, 40)]);

            return back()->withErrors(['login' => __("Login yoki parol noto'g'ri.")])->onlyInput('login');
        }

        RateLimiter::clear($key);

        // Ikki bosqichli tasdiqlash yoqilgan bo'lsa — kod so'raladi
        if ($user->hasTwoFactor()) {
            $r->session()->regenerate();
            session(['2fa' => ['id' => $user->id, 'remember' => $r->boolean('remember'), 'exp' => now()->addMinutes(10)->timestamp]]);

            return redirect()->route('login.2fa');
        }

        return $this->finishLogin($r, $user, $r->boolean('remember'));
    }

    protected function finishLogin(Request $r, User $user, bool $remember)
    {
        Auth::login($user, $remember);
        $r->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();
        session(['locale' => $user->locale]);
        AuditLog::record('login', $user->id, $user->clinic_id);

        return redirect()->route('dashboard');
    }

    public function twoFactorForm(Request $r)
    {
        $p = session('2fa');
        if (! $p || $p['exp'] < time()) {
            session()->forget('2fa');

            return redirect()->route('login');
        }

        return view('auth.two-factor');
    }

    public function twoFactor(Request $r)
    {
        $p = session('2fa');
        if (! $p || $p['exp'] < time() || ! ($user = User::find($p['id'])) || ! $user->is_active || ! $user->hasTwoFactor()) {
            session()->forget('2fa');

            return redirect()->route('login')->withErrors(['login' => __('Sessiya tugadi. Qaytadan kiring.')]);
        }
        $r->validate(['code' => 'required|string|max:30']);
        $key = '2fa:'.$user->id.'|'.$r->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['code' => __("Juda ko'p urinish. :s soniyadan keyin qayta urinib ko'ring.", ['s' => RateLimiter::availableIn($key)])]);
        }

        $code = trim($r->input('code'));
        $ok = false;
        $win = Totp::verify($user->two_factor_secret, $code);
        if ($win !== null && Cache::add('2fa-used:'.$user->id.':'.$win, 1, 120)) {
            $ok = true; // bir xil kodni ikki marta ishlatib bo'lmaydi
        } else {
            $codes = $user->two_factor_recovery_codes ?: [];
            $norm = Str::lower(str_replace(' ', '', $code));
            foreach ($codes as $i => $rc) {
                if (hash_equals(Str::lower($rc), $norm)) {
                    unset($codes[$i]);
                    $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->saveQuietly();
                    AuditLog::record('2fa_recovery', $user->id, $user->clinic_id);
                    $ok = true;
                    break;
                }
            }
        }
        if (! $ok) {
            RateLimiter::hit($key, 300);
            AuditLog::record('2fa_failed', $user->id, $user->clinic_id);

            return back()->withErrors(['code' => __("Kod noto'g'ri.")]);
        }
        RateLimiter::clear($key);
        session()->forget('2fa');

        return $this->finishLogin($r, $user, (bool) $p['remember']);
    }

    public function logout(Request $r)
    {
        if ($r->user()) {
            AuditLog::record('logout', $r->user()->id, $r->user()->clinic_id);
        }
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
            'password' => ['required', 'string', 'max:100', 'confirmed', new \App\Rules\StrongPassword],
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
        // Asosiy kanal — Telegram bot (verify sahifasida). Bot sozlanmagan bo'lsa — SMS (modem → Eskiz)
        if (! TelegramOtp::available()) {
            $this->sendCode($phone, 'register');
        }

        return redirect()->route('register.verify');
    }

    public function verifyForm()
    {
        if (! session('reg')) {
            return redirect()->route('register');
        }

        return view('auth.verify', ['phone' => session('reg.phone'), 'tg' => TelegramOtp::available()]);
    }

    public function resend()
    {
        $reg = session('reg');
        if (! $reg) {
            return redirect()->route('register');
        }
        $key = 'resend:'.$reg['phone'];
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->withErrors(['code' => __("Juda ko'p urinish. :s soniyadan keyin qayta urinib ko'ring.", ['s' => RateLimiter::availableIn($key)])]);
        }
        RateLimiter::hit($key, 600);
        $this->sendCode($reg['phone'], 'register');

        return back()->with('success', __('Kod qayta yuborildi.'));
    }

    public function verify(Request $r)
    {
        $reg = session('reg');
        if (! $reg) {
            return redirect()->route('register');
        }
        $r->validate(['code' => 'required|digits:6']);

        if ($err = OtpCodes::check($reg['phone'], 'register', (string) $r->input('code'))) {
            return back()->withErrors(['code' => $err]);
        }
        if (User::where('phone', $reg['phone'])->exists() || Clinic::where('phone', $reg['phone'])->exists()) {
            session()->forget('reg');

            return redirect()->route('login')->withErrors(['login' => __("Bu raqam bilan allaqachon ro'yxatdan o'tilgan.")]);
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
                'user_id' => $user->id,
                'context' => 'register',
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

        session()->forget('reg');
        Auth::login($user);
        $r->session()->regenerate();

        return redirect()->route('app.profile.edit')->with('success', __("Xush kelibsiz! Demo davringiz boshlandi. Avval klinika ma'lumotlarini to'ldiring."));
    }

    /** SMS orqali kod (zaxira kanal): platforma SMS zanjiri — avval modem, so'ng Eskiz. */
    protected function sendCode(string $phone, string $purpose): void
    {
        $code = OtpCodes::issue($phone, $purpose, 'sms');
        $sent = PlatformSms::send('otp', $phone, ['code' => $code]);
        if (! $sent && config('app.debug')) {
            session()->flash('dev_code', $code); // faqat test rejimida (APP_DEBUG=true va SMS sozlanmagan)
        }
    }

    // ---------- Telegram orqali tasdiqlash (ro'yxatdan o'tish va parolni tiklash) ----------

    /** Sessiyadagi jarayon: register yoki reset. Telefon HECH QACHON mijozdan olinmaydi. */
    protected function flowPhone(string $flow): ?string
    {
        return match ($flow) {
            'register' => session('reg.phone'),
            'reset' => session('reset.phone'),
            default => null,
        };
    }

    public function telegramOtp(Request $r)
    {
        $flow = (string) $r->input('flow');
        $phone = $this->flowPhone($flow);
        if (! $phone) {
            return response()->json(['ok' => false, 'message' => __('Sessiya tugadi. Qaytadan boshlang.')], 422);
        }
        $key = 'tg-otp:'.$phone;
        if (RateLimiter::tooManyAttempts($key, 5) || RateLimiter::tooManyAttempts('tg-otp-ip:'.$r->ip(), 20)) {
            return response()->json(['ok' => false, 'message' => __("Juda ko'p urinish. Keyinroq qayta urinib ko'ring.")], 429);
        }
        RateLimiter::hit($key, 600);
        RateLimiter::hit('tg-otp-ip:'.$r->ip(), 600);

        return response()->json(TelegramOtp::request($phone, $flow, $r->ip()));
    }

    public function telegramOtpStatus(Request $r)
    {
        if (! session()->has('reg') && ! session()->has('reset')) {
            return response()->json(['state' => 'expired', 'message' => __('Sessiya tugadi. Qaytadan boshlang.')]);
        }

        return response()->json(TelegramOtp::status((string) $r->query('token')));
    }

    // ---------- Parolni tiklash ----------

    public function forgotForm()
    {
        return view('auth.forgot');
    }

    public function forgot(Request $r)
    {
        $d = $r->validate(['phone' => ['required', 'string', 'regex:/^\+?[0-9\s\-\(\)]{9,20}$/']]);
        $phone = SmsService::normalize($d['phone']);
        if (strlen($phone) !== 12 || ! str_starts_with($phone, '998')) {
            return back()->withErrors(['phone' => __("Telefon raqami +998 XX XXX XX XX ko'rinishida bo'lishi kerak.")])->onlyInput('phone');
        }
        $key = 'forgot:'.$r->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return back()->withErrors(['phone' => __("Juda ko'p urinish. Keyinroq qayta urinib ko'ring.")])->onlyInput('phone');
        }
        RateLimiter::hit($key, 3600);

        session(['reset' => ['phone' => $phone]]);
        // Hisob borligi oshkor qilinmaydi: har doim keyingi sahifaga o'tiladi
        if (! TelegramOtp::available() && TelegramOtp::resetUser($phone)) {
            $this->sendCode($phone, 'reset');
        }

        return redirect()->route('password.verify');
    }

    public function forgotVerifyForm()
    {
        if (! session('reset')) {
            return redirect()->route('password.forgot');
        }

        return view('auth.forgot-verify', ['phone' => session('reset.phone'), 'tg' => TelegramOtp::available()]);
    }

    public function forgotReset(Request $r)
    {
        $phone = session('reset.phone');
        if (! $phone) {
            return redirect()->route('password.forgot');
        }
        $d = $r->validate([
            'code' => 'required|digits:6',
            'password' => ['required', 'string', 'max:100', 'confirmed', new \App\Rules\StrongPassword],
        ]);
        $key = 'reset:'.$phone;
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return back()->withErrors(['code' => __("Juda ko'p urinish. :s soniyadan keyin qayta urinib ko'ring.", ['s' => RateLimiter::availableIn($key)])]);
        }
        RateLimiter::hit($key, 900);

        if ($err = OtpCodes::check($phone, 'reset', $d['code'])) {
            return back()->withErrors(['code' => $err]);
        }
        $user = TelegramOtp::resetUser($phone);
        if (! $user) {
            return back()->withErrors(['code' => __("Kod noto'g'ri.")]);
        }
        $user->forceFill(['password' => $d['password'], 'remember_token' => Str::random(60)])->save();
        AuditLog::record('password_reset', $user->id, $user->clinic_id);
        RateLimiter::clear($key);
        session()->forget('reset');

        return redirect()->route('login')->with('success', __('Parol yangilandi. Yangi parol bilan kiring.'));
    }
}
