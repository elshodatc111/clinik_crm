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
use App\Services\SmsService;
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
        $key = 'resend:'.$reg['phone'];
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->withErrors(['code' => __("Juda ko'p urinish. :s soniyadan keyin qayta urinib ko'ring.", ['s' => RateLimiter::availableIn($key)])]);
        }
        RateLimiter::hit($key, 600);
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
        $sent = SmsService::send($phone, 'Tasdiqlash kodi: '.$code.'. '.BillingService::appName(), null, false, 'otp');
        if (! $sent && config('app.debug')) {
            session()->flash('dev_code', $code); // faqat test rejimida (APP_DEBUG=true va Eskiz sozlanmagan)
        }
    }
}
