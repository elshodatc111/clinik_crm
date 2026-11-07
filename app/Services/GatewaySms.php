<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

/**
 * Bizning SMS Gateway (USB modem, sms.elshod.date). FAQAT platforma xabarlari uchun
 * (ro'yxatdan o'tish kodi zaxirasi, obuna xabarlari). Klinika bemorlariga SMS har doim klinikaning o'z Eskiz akkaunti orqali ketadi.
 *
 * @phpstan-type Result array{ok:bool,fallback:bool,note:?string}
 */
class GatewaySms
{
    /** Eskiz'ga O'TMAYDIGAN xatolar: so'rovning o'zi noto'g'ri yoki spamdan himoya. */
    private const NO_FALLBACK = ['invalid_phone', 'empty_text', 'validation_error', 'rate_limited'];

    public static function url(): string
    {
        return rtrim((string) Setting::get('sms_gateway_url', 'https://sms.elshod.date'), '/');
    }

    public static function key(): string
    {
        return (string) Setting::get('sms_gateway_key', '');
    }

    public static function enabled(): bool
    {
        return static::key() !== '' && Setting::get('sms_gateway_on', '1') === '1';
    }

    /** Gateway sog'lig'i; aloqa bo'lmasa null. */
    public static function health(): ?array
    {
        try {
            $r = Http::acceptJson()->timeout(4)->get(static::url().'/api/v1/health');
        } catch (\Throwable) {
            return null;
        }

        return is_array($r->json()) && $r->json('status') !== null ? (array) $r->json() : null;
    }

    /** @return array{ok:bool,fallback:bool,note:?string} */
    public static function send(string $phone, string $text): array
    {
        $h = static::health();
        if ($h === null) {
            return ['ok' => false, 'fallback' => true, 'note' => "Gateway bilan aloqa o'rnatilmadi"];
        }
        if (($h['status'] ?? '') === 'down' || (int) ($h['modems_usable'] ?? 0) < 1) {
            return ['ok' => false, 'fallback' => true, 'note' => "Gateway: tayyor modem yo'q"];
        }

        $wait = (int) Setting::get('sms_gateway_wait', 12);
        try {
            $r = Http::withToken(static::key())->acceptJson()->timeout(8)->post(static::url().'/api/v1/sms/send', [
                'to' => '+'.ltrim($phone, '+'), 'text' => $text, 'external_id' => 'kl-'.bin2hex(random_bytes(8)),
            ]);
        } catch (\Throwable) {
            return ['ok' => false, 'fallback' => true, 'note' => "Gateway bilan aloqa o'rnatilmadi"];
        }
        $id = $r->json('id');
        if (! $r->successful() || $id === null) {
            $code = (string) $r->json('error.code');

            return ['ok' => false, 'fallback' => ! in_array($code, self::NO_FALLBACK, true),
                'note' => 'Gateway: '.($code !== '' ? $code.': '.mb_substr((string) $r->json('error.message'), 0, 150) : 'HTTP '.$r->status())];
        }

        return static::await((int) $id, (string) $r->json('status'), $wait);
    }

    /** Natijani kutadi; vaqt tugasa navbatdan olib tashlaymiz (ikki marta SMS bormasligi uchun). */
    protected static function await(int $id, string $status, int $wait): array
    {
        $deadline = microtime(true) + max(0, $wait);
        $error = null;
        while (true) {
            if ($status === 'sent') {
                return ['ok' => true, 'fallback' => false, 'note' => 'gateway'];
            }
            if (in_array($status, ['failed', 'expired', 'cancelled'], true)) {
                return ['ok' => false, 'fallback' => true, 'note' => 'Gateway: '.($error ?: $status)];
            }
            if (microtime(true) >= $deadline) {
                break;
            }
            usleep(1_000_000);
            try {
                $s = Http::withToken(static::key())->acceptJson()->timeout(5)->get(static::url().'/api/v1/sms/'.$id);
                if ($s->successful()) {
                    $status = (string) $s->json('status');
                    $error = $s->json('error');
                }
            } catch (\Throwable) {
            }
        }
        try {
            $c = Http::withToken(static::key())->acceptJson()->timeout(5)->delete(static::url().'/api/v1/sms/'.$id);
            if ($c->successful()) {
                return ['ok' => false, 'fallback' => true, 'note' => 'Gateway: kutish vaqti tugadi (navbatdan olindi)'];
            }
        } catch (\Throwable) {
        }

        return ['ok' => true, 'fallback' => false, 'note' => 'gateway'];
    }
}
