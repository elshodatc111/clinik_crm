<?php

namespace App\Services;

use App\Models\Clinic;
use App\Models\PinflLookup;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

/**
 * JSHSHIR bo'yicha F.I.Sh. olish. Provayder bilan shartnomani KLINIKANING O'ZI tuzadi va
 * API ma'lumotlarini sozlamalarga kiritadi. Umumiy (sozlanuvchi) HTTP provayder:
 * URL, usul, avtorizatsiya va javob maydonlari xaritasi klinika tomonidan beriladi.
 */
class PinflLookupService
{
    public const FIELDS = ['last_name', 'first_name', 'middle_name', 'birth_date', 'gender', 'address'];

    public static function enabled(Clinic $c): bool
    {
        return (bool) ($c->setting('pinfl_enabled') && $c->setting('pinfl_url'));
    }

    public static function secret(Clinic $c): ?string
    {
        $enc = $c->setting('pinfl_secret');
        try {
            return $enc ? Crypt::decryptString($enc) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** SSRF himoyasi: faqat ommaviy HTTPS manzillar. */
    public static function assertSafeUrl(string $url): void
    {
        $p = parse_url($url);
        if (($p['scheme'] ?? '') !== 'https' || empty($p['host'])) {
            throw new \InvalidArgumentException(__("API manzili https:// bilan boshlanishi kerak."));
        }
        $host = $p['host'];
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
        if (! $ips) {
            throw new \InvalidArgumentException(__("API manzili topilmadi (DNS)."));
        }
        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new \InvalidArgumentException(__("Ichki tarmoq manzillariga ruxsat yo'q."));
            }
        }
    }

    /**
     * @return array{data: ?array, status: string, keys: string[]}  status: ok | not_found
     *
     * @throws \Throwable xato bo'lsa (xabar foydalanuvchiga ko'rsatiladi)
     */
    public static function fetch(Clinic $c, string $pinfl): array
    {
        $url = str_replace('{pinfl}', rawurlencode($pinfl), (string) $c->setting('pinfl_url'));
        static::assertSafeUrl($url);

        $http = Http::timeout(10)->withoutRedirecting()->acceptJson();
        $secret = static::secret($c);
        $name = (string) $c->setting('pinfl_auth_name');
        switch ($c->setting('pinfl_auth', 'none')) {
            case 'bearer':
                $http = $http->withToken((string) $secret);
                break;
            case 'header':
                $http = $http->withHeaders([$name ?: 'X-Api-Key' => (string) $secret]);
                break;
            case 'basic':
                $http = $http->withBasicAuth($name, (string) $secret);
                break;
            case 'query':
                $url .= (str_contains($url, '?') ? '&' : '?').rawurlencode($name ?: 'api_key').'='.rawurlencode((string) $secret);
                break;
        }

        if (strtoupper((string) $c->setting('pinfl_method', 'GET')) === 'POST') {
            $tpl = (string) ($c->setting('pinfl_body') ?: '{"pinfl":"{pinfl}"}');
            $body = json_decode(str_replace('{pinfl}', $pinfl, $tpl), true);
            $res = $http->asJson()->post($url, is_array($body) ? $body : ['pinfl' => $pinfl]);
        } else {
            $res = $http->get($url);
        }

        if ($res->status() === 404) {
            return ['data' => null, 'status' => 'not_found', 'keys' => []];
        }
        if (! $res->successful()) {
            throw new \RuntimeException(__('Provayder xatosi (HTTP :c)', ['c' => $res->status()]));
        }
        $json = $res->json();
        if (! is_array($json)) {
            throw new \RuntimeException(__("Provayder JSON qaytarmadi."));
        }
        $root = (string) $c->setting('pinfl_root');
        $node = $root !== '' ? data_get($json, $root) : $json;
        $keys = is_array($node) ? static::flatKeys($node) : [];

        $map = (array) $c->setting('pinfl_map', []);
        $out = [];
        foreach (static::FIELDS as $f) {
            $path = trim((string) ($map[$f] ?? ''));
            $v = $path !== '' && is_array($node) ? data_get($node, $path) : null;
            $out[$f] = is_scalar($v) ? trim((string) $v) : null;
        }
        if (! $out['last_name'] && ! $out['first_name']) {
            return ['data' => null, 'status' => 'not_found', 'keys' => $keys];
        }

        return ['data' => static::normalize($out), 'status' => 'ok', 'keys' => $keys];
    }

    /** Ishlatuvchi uchun: natija + jurnal. */
    public static function lookup(Clinic $c, string $pinfl, ?int $userId = null): ?array
    {
        $masked = substr($pinfl, 0, 3).'********'.substr($pinfl, -3);
        try {
            $r = static::fetch($c, $pinfl);
        } catch (\Throwable $e) {
            PinflLookup::create(['clinic_id' => $c->id, 'user_id' => $userId, 'pinfl_masked' => $masked, 'status' => 'error', 'note' => mb_substr($e->getMessage(), 0, 200)]);
            throw $e;
        }
        PinflLookup::create(['clinic_id' => $c->id, 'user_id' => $userId, 'pinfl_masked' => $masked, 'status' => $r['status']]);

        return $r['data'];
    }

    protected static function normalize(array $o): array
    {
        $cap = fn (?string $s) => $s === null || $s === '' ? null : implode(' ', array_map(fn ($w) => ucfirst(mb_strtolower($w)), preg_split('/\s+/u', trim($s))));
        foreach (['last_name', 'first_name', 'middle_name'] as $k) {
            $o[$k] = $cap($o[$k]);
        }
        if ($o['birth_date']) {
            try {
                $o['birth_date'] = Carbon::parse($o['birth_date'])->toDateString();
            } catch (\Throwable $e) {
                $o['birth_date'] = null;
            }
        }
        $g = mb_strtolower((string) $o['gender']);
        $o['gender'] = in_array($g, ['1', 'm', 'male', 'erkak', 'муж', 'мужской', 'man'], true) ? 'm'
            : (in_array($g, ['2', 'f', 'female', 'ayol', 'жен', 'женский', 'woman'], true) ? 'f' : null);

        return $o;
    }

    /** Javob tuzilmasi (faqat kalit yo'llari, qiymatlarsiz) — moslashtirish uchun. */
    protected static function flatKeys(array $a, string $prefix = '', int $depth = 0): array
    {
        $out = [];
        foreach ($a as $k => $v) {
            $path = $prefix === '' ? (string) $k : $prefix.'.'.$k;
            if (is_array($v) && $depth < 3) {
                $out = array_merge($out, static::flatKeys($v, $path, $depth + 1));
            } else {
                $out[] = $path;
            }
        }

        return array_slice($out, 0, 60);
    }
}
