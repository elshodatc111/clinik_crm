<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/** Bir martalik kodlar (sms_codes): yaratish va tekshirish. Kanal (telegram/sms) kodning o'ziga aloqasiz. */
class OtpCodes
{
    public const TTL_MINUTES = 5;
    public const MAX_ATTEMPTS = 5;

    /** Yangi kod yaratadi (eskisini almashtiradi). Ochiq kodni qaytaradi. */
    public static function issue(string $phone, string $purpose, string $channel): string
    {
        DB::table('sms_codes')->where('phone', $phone)->where('purpose', $purpose)->delete();
        $code = (string) random_int(100000, 999999);
        DB::table('sms_codes')->insert([
            'phone' => $phone, 'purpose' => $purpose, 'channel' => $channel,
            'code' => Hash::make($code), 'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            'attempts' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $code;
    }

    /** @return string|null xato matni; null — kod to'g'ri (kod ishlatilgan deb o'chiriladi) */
    public static function check(string $phone, string $purpose, string $code): ?string
    {
        $row = DB::table('sms_codes')->where('phone', $phone)->where('purpose', $purpose)->first();
        if (! $row || now()->greaterThan(Carbon::parse($row->expires_at))) {
            return __('Kod muddati tugagan. Qayta yuboring.');
        }
        if ($row->attempts >= self::MAX_ATTEMPTS) {
            return __('Urinishlar soni oshdi. Kodni qayta yuboring.');
        }
        DB::table('sms_codes')->where('id', $row->id)->increment('attempts');
        if (! Hash::check($code, $row->code)) {
            return __("Kod noto'g'ri.");
        }
        DB::table('sms_codes')->where('id', $row->id)->delete();

        return null;
    }
}
