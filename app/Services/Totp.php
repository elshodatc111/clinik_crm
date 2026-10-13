<?php

namespace App\Services;

/** RFC 6238 TOTP (Google Authenticator, Authy, 1Password va boshqalar bilan mos). */
class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function secret(int $bytes = 20): string
    {
        return static::base32Encode(random_bytes($bytes));
    }

    public static function code(string $secret, ?int $time = null, int $digits = 6, int $period = 30): string
    {
        $counter = intdiv($time ?? time(), $period);
        $bin = pack('N2', $counter >> 32, $counter & 0xFFFFFFFF);
        $hash = hash_hmac('sha1', $bin, static::base32Decode($secret), true);
        $o = ord($hash[19]) & 0x0F;
        $n = ((ord($hash[$o]) & 0x7F) << 24) | (ord($hash[$o + 1]) << 16) | (ord($hash[$o + 2]) << 8) | ord($hash[$o + 3]);

        return str_pad((string) ($n % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
    }

    /** ±1 oyna (soat farqi uchun). To'g'ri bo'lsa, ishlatilgan vaqt oynasini qaytaradi (qayta ishlatishni oldini olish uchun). */
    public static function verify(string $secret, string $input, int $window = 1, ?int $time = null): ?int
    {
        $input = preg_replace('/\D/', '', $input);
        if (strlen($input) !== 6) {
            return null;
        }
        $time ??= time();
        for ($i = -$window; $i <= $window; $i++) {
            $t = $time + $i * 30;
            if (hash_equals(static::code($secret, $t), $input)) {
                return intdiv($t, 30);
            }
        }

        return null;
    }

    public static function uri(string $secret, string $account, string $issuer): string
    {
        return 'otpauth://totp/'.rawurlencode($issuer.':'.$account).'?secret='.$secret.'&issuer='.rawurlencode($issuer).'&algorithm=SHA1&digits=6&period=30';
    }

    public static function base32Encode(string $bin): string
    {
        $bits = '';
        foreach (str_split($bin) as $c) {
            $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::ALPHABET[bindec(str_pad($chunk, 5, '0'))];
        }

        return $out;
    }

    public static function base32Decode(string $s): string
    {
        $s = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $s));
        $bits = '';
        foreach (str_split($s) as $c) {
            $bits .= str_pad(decbin(strpos(self::ALPHABET, $c)), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr(bindec($byte));
            }
        }

        return $out;
    }
}
