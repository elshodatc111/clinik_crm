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
    public static function send(?string $phone, string $text, ?int $clinicId = null, bool $viaClinic = false, ?string $event = null): bool
    {
        $phone = static::normalize($phone);
        if (strlen($phone) < 12) {
            return false;
        }
        $log = SmsLog::create(['clinic_id' => $clinicId, 'phone' => $phone, 'message' => $event === 'otp' ? preg_replace('/\d{6}/', '••••••', $text) : $text, 'status' => 'pending', 'event' => $event]);

        // Klinika SMS xizmatini o'chirib qo'ygan bo'lsa, bemorlarga hech narsa yuborilmaydi
        if ($viaClinic && $clinicId && Clinic::find($clinicId)?->setting('sms_enabled', true) === false) {
            $log->update(['status' => 'skipped', 'response' => "SMS xizmati klinika tomonidan o'chirilgan"]);

            return false;
        }

        $creds = $viaClinic ? static::clinicCreds($clinicId) : static::platformCreds();
        if (! $creds) {
            Log::info("SMS (Eskiz sozlanmagan) -> {$phone}: ".($event === 'otp' ? '[kod yashirilgan]' : $text));
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

    /**
     * Hodisa bo'yicha SMS: yoqilganligi, Eskiz tasdig'i va shablon matni tekshiriladi.
     * @param Clinic|int $clinic
     */
    public static function event($clinic, string $event, ?string $phone, array $vars = []): bool
    {
        $c = $clinic instanceof Clinic ? $clinic : Clinic::find($clinic);
        if (! $c || ! $phone) {
            return false;
        }
        $tpl = \App\Support\SmsEvents::get($c, $event);
        if (! $tpl['enabled'] || $c->setting('sms_enabled', true) === false) {
            return false;
        }
        $text = \App\Support\SmsEvents::render($tpl['text'], $vars + ['clinic' => $c->name, 'address' => (string) $c->address]);
        if ($c->setting('sms_need_approval', true) && $tpl['status'] !== 'approved') {
            $log = SmsLog::create(['clinic_id' => $c->id, 'phone' => static::normalize($phone), 'message' => $text, 'status' => 'skipped', 'event' => $event,
                'response' => 'Shablon Eskiz tomonidan tasdiqlanmagan ('.\App\Support\SmsEvents::statusLabel($tpl['status']).')']);

            return false;
        }

        return static::send($phone, $text, $c->id, true, $event);
    }

    // ---------------- Eskiz shablonlari ----------------

    /** @return array{ok:bool,id:?int,message:?string} */
    public static function submitTemplate(array $creds, string $text): array
    {
        try {
            $res = Http::timeout(20)->withToken(static::token($creds))->asForm()->post('https://notify.eskiz.uz/api/user/template', ['template' => $text]);
            if ($res->status() === 401) {
                Cache::forget($creds['key']);
                $res = Http::timeout(20)->withToken(static::token($creds))->asForm()->post('https://notify.eskiz.uz/api/user/template', ['template' => $text]);
            }
            $id = $res->json('id') ?? $res->json('data.id') ?? $res->json('result.id') ?? $res->json('template.id');

            return ['ok' => $res->successful(), 'id' => $id ? (int) $id : null, 'message' => $res->successful() ? null : mb_substr((string) ($res->json('message') ?: $res->body()), 0, 300)];
        } catch (\Throwable $e) {
            return ['ok' => false, 'id' => null, 'message' => $e->getMessage()];
        }
    }

    /** @return array{ok:bool,rows:array,message:?string} */
    public static function fetchTemplates(array $creds): array
    {
        try {
            $res = Http::timeout(20)->withToken(static::token($creds))->get('https://notify.eskiz.uz/api/user/templates');
            if ($res->status() === 401) {
                Cache::forget($creds['key']);
                $res = Http::timeout(20)->withToken(static::token($creds))->get('https://notify.eskiz.uz/api/user/templates');
            }
            $rows = $res->json('result') ?? $res->json('data') ?? $res->json();

            return ['ok' => $res->successful() && is_array($rows), 'rows' => is_array($rows) ? array_values($rows) : [], 'message' => $res->successful() ? null : mb_substr($res->body(), 0, 300)];
        } catch (\Throwable $e) {
            return ['ok' => false, 'rows' => [], 'message' => $e->getMessage()];
        }
    }

    /** Eskiz holatini bizning holatga o'tkazish. */
    public static function mapTemplateStatus(?string $s): string
    {
        $s = strtolower(trim((string) $s));
        if (in_array($s, ['service', 'approved', 'active', 'accepted', 'confirmed', 'success', 'verified'], true)) {
            return 'approved';
        }
        if (in_array($s, ['reject', 'rejected', 'declined', 'cancel', 'canceled', 'cancelled', 'blocked'], true)) {
            return 'rejected';
        }

        return 'moderation';
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
