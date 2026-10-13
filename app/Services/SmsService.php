<?php

namespace App\Services;

use App\Models\Clinic;
use App\Models\Setting;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** Eskiz.uz orqali SMS yuborish. Sozlanmagan bo'lsa SMS faqat log'ga yoziladi. */
class SmsService
{
    public static function normalize(?string $phone): string
    {
        $d = preg_replace('/\D+/', '', (string) $phone);
        if (strlen($d) === 9) {
            return '998'.$d;
        }

        return $d;
    }

    public static function isConfigured(): bool
    {
        return (bool) (Setting::get('eskiz_email') && Setting::get('eskiz_password'));
    }

    /**
     * @param bool $viaClinic true bo'lsa SMS klinikaning O'Z Eskiz akkaunti orqali yuboriladi
     *                        (bemorlarga xabarlar). Ulanmagan bo'lsa umumiy akkauntga O'TILMAYDI.
     *                        false — platforma xabarlari (OTP, obuna xabarlari) umumiy akkaunt orqali.
     */
    public static function send(?string $phone, string $text, ?int $clinicId = null, bool $viaClinic = false): bool
    {
        $phone = static::normalize($phone);
        if (strlen($phone) < 12) {
            return false;
        }
        $log = SmsLog::create(['clinic_id' => $clinicId, 'phone' => $phone, 'message' => $text, 'status' => 'pending']);

        $creds = $viaClinic ? static::clinicCreds($clinicId) : static::platformCreds();
        if (! $creds) {
            Log::info("SMS (Eskiz sozlanmagan) -> {$phone}: {$text}");
            $log->update(['status' => 'skipped', 'response' => $viaClinic ? 'Klinika Eskiz akkaunti ulanmagan' : 'Eskiz sozlanmagan']);

            return false;
        }

        try {
            $res = static::post($creds, $phone, $text);
            if ($res->status() === 401) {
                Cache::forget($creds['key']);
                $res = static::post($creds, $phone, $text);
            }
            $ok = $res->successful();
            $log->update(['status' => $ok ? 'sent' : 'failed', 'response' => mb_substr($res->body(), 0, 1000)]);

            return $ok;
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'response' => mb_substr($e->getMessage(), 0, 1000)]);

            return false;
        }
    }

    public static function platformCreds(): ?array
    {
        if (! static::isConfigured()) {
            return null;
        }

        return ['key' => 'eskiz_token', 'email' => Setting::get('eskiz_email'), 'password' => Setting::get('eskiz_password'), 'from' => Setting::get('eskiz_from', '4546')];
    }

    public static function clinicCreds(?int $clinicId): ?array
    {
        $c = $clinicId ? Clinic::find($clinicId) : null;
        $email = $c?->setting('eskiz_email');
        $enc = $c?->setting('eskiz_password');
        if (! $email || ! $enc) {
            return null;
        }
        try {
            $pass = Crypt::decryptString($enc);
        } catch (\Throwable $e) {
            return null;
        }

        return ['key' => 'eskiz_token_c'.$c->id, 'email' => $email, 'password' => $pass, 'from' => $c->setting('eskiz_from') ?: '4546'];
    }

    /** Klinika Eskiz akkaunti to'g'ri ulanganini tekshirish (login qilib ko'radi). */
    public static function checkLogin(array $creds): ?string
    {
        try {
            $res = Http::timeout(15)->asForm()->post('https://notify.eskiz.uz/api/auth/login', ['email' => $creds['email'], 'password' => $creds['password']]);

            return $res->successful() && $res->json('data.token') ? null : (string) ($res->json('message') ?: 'Login yoki parol noto\'g\'ri');
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    protected static function post(array $creds, string $phone, string $text)
    {
        return Http::timeout(15)->withToken(static::token($creds))->asForm()->post('https://notify.eskiz.uz/api/message/sms/send', [
            'mobile_phone' => $phone,
            'message' => $text,
            'from' => $creds['from'],
        ]);
    }

    protected static function token(array $creds): string
    {
        return Cache::remember($creds['key'], now()->addDays(25), function () use ($creds) {
            $res = Http::timeout(15)->asForm()->post('https://notify.eskiz.uz/api/auth/login', [
                'email' => $creds['email'],
                'password' => $creds['password'],
            ]);

            return (string) $res->json('data.token');
        });
    }
}
