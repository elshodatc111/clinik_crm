<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\BillingService;
use App\Services\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/** Akkaunt uchun ikki bosqichli tasdiqlash (TOTP) sozlamalari. */
class TwoFactorController extends Controller
{
    public function start(Request $r)
    {
        abort_if($r->user()->hasTwoFactor(), 404);
        session(['2fa_setup' => Totp::secret()]);

        return redirect()->to(route('account.edit').'#twofactor');
    }

    public function confirm(Request $r)
    {
        $u = $r->user();
        $secret = session('2fa_setup');
        $r->validate(['code' => 'required|string|max:10']);
        if (! $secret) {
            return redirect()->route('account.edit');
        }
        if (Totp::verify($secret, $r->input('code')) === null) {
            return redirect()->to(route('account.edit').'#twofactor')->withErrors(['code' => __("Kod noto'g'ri.")], 'twofactor');
        }
        $codes = static::newCodes();
        $u->forceFill(['two_factor_secret' => $secret, 'two_factor_recovery_codes' => $codes, 'two_factor_confirmed_at' => now()])->save();
        session()->forget('2fa_setup');
        AuditLog::record('2fa_on', $u->id, $u->clinic_id);

        return redirect()->to(route('account.edit').'#twofactor')->with('recovery', $codes)->with('success', __('Ikki bosqichli tasdiqlash yoqildi. Zaxira kodlarni xavfsiz joyda saqlang.'));
    }

    public function disable(Request $r)
    {
        $u = $r->user();
        $key = '2fa-off:'.$u->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['password' => __("Juda ko'p urinish. :s soniyadan keyin qayta urinib ko'ring.", ['s' => RateLimiter::availableIn($key)])], 'twofactor');
        }
        $r->validate(['password' => 'required|string|max:200']);
        if (! Hash::check($r->input('password'), $u->password)) {
            RateLimiter::hit($key, 300);

            return back()->withErrors(['password' => __("Joriy parol noto'g'ri.")], 'twofactor');
        }
        $u->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();
        AuditLog::record('2fa_off', $u->id, $u->clinic_id);

        return redirect()->to(route('account.edit').'#twofactor')->with('success', __("Ikki bosqichli tasdiqlash o'chirildi."));
    }

    public function recovery(Request $r)
    {
        $u = $r->user();
        abort_unless($u->hasTwoFactor(), 404);
        $r->validate(['password' => 'required|string|max:200']);
        if (! Hash::check($r->input('password'), $u->password)) {
            return back()->withErrors(['password' => __("Joriy parol noto'g'ri.")], 'twofactor');
        }
        $codes = static::newCodes();
        $u->forceFill(['two_factor_recovery_codes' => $codes])->save();

        return redirect()->to(route('account.edit').'#twofactor')->with('recovery', $codes);
    }

    public static function newCodes(): array
    {
        return collect(range(1, 8))->map(fn () => Str::lower(Str::random(5).'-'.Str::random(5)))->all();
    }

    public static function setupData(Request $r): ?array
    {
        $secret = session('2fa_setup');
        if (! $secret || $r->user()->hasTwoFactor()) {
            return null;
        }

        return ['secret' => $secret, 'uri' => Totp::uri($secret, $r->user()->phone ?: ($r->user()->email ?: 'user'), BillingService::appName())];
    }
}
