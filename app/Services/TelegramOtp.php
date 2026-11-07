<?php

namespace App\Services;

use App\Models\Clinic;
use App\Models\OtpTelegramLink;
use App\Models\OtpTelegramRequest;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Telefon raqamni Telegram bot orqali tasdiqlash (platforma boti).
 *
 * Oqim: sayt request() chaqiradi → (telefon avval ulangan bo'lsa kod darhol Telegramga ketadi, aks holda
 * t.me/<bot>?start=o_<token> havola qaytadi) → botda «Start» va «Raqamni ulashish» → kontakt shu
 * foydalanuvchining o'zinikimi va kiritilgan raqamga tengmi tekshiriladi → kod chatga yuboriladi.
 * Kodni tekshirish — OtpCodes::check().
 */
class TelegramOtp
{
    public const PURPOSES = ['register', 'reset'];
    public const REQUEST_MINUTES = 10;
    public const LINK_DAYS = 180;

    public static function token(): ?string
    {
        return Setting::get('tg_bot_token') ?: null;
    }

    public static function username(): ?string
    {
        return Setting::get('tg_bot_username') ?: null;
    }

    public static function api(): ?TelegramApi
    {
        $t = static::token();

        return $t ? new TelegramApi($t) : null;
    }

    public static function available(): bool
    {
        return static::token() && static::username()
            && Setting::get('tg_bot_status') === 'ok'
            && Setting::get('tg_otp_enabled', '1') === '1';
    }

    public static function webhookSecret(): string
    {
        $s = Setting::get('tg_webhook_secret');
        if (! $s) {
            $s = Str::random(40);
            Setting::set('tg_webhook_secret', $s);
        }

        return $s;
    }

    public static function startLink(string $payload): string
    {
        return 'https://t.me/'.static::username().'?start='.$payload;
    }

    /**
     * @return array{ok:bool,message:string,sent?:bool,link?:string,token?:string}
     */
    public static function request(string $phone, string $purpose, ?string $ip): array
    {
        if (! in_array($purpose, self::PURPOSES, true) || strlen($phone) !== 12) {
            return ['ok' => false, 'message' => __("Telefon raqami noto'g'ri.")];
        }
        if (! static::available()) {
            return ['ok' => false, 'message' => __('Telegram orqali tasdiqlash hozircha mavjud emas.')];
        }

        // Avval ulangan chat bo'lsa — kodni darhol yuboramiz (reset uchun faqat hisob bor bo'lsa; aks holda hisob borligini oshkor qilmaymiz)
        $chat = static::knownChat($phone);
        if ($chat && ($purpose === 'register' || static::resetUser($phone))) {
            $res = static::deliver($phone, $purpose, $chat);
            if ($res['ok']) {
                return ['ok' => true, 'sent' => true, 'message' => __('Kod Telegramga yuborildi. Telegramni oching.')];
            }
            if ($res['final'] ?? false) {
                return ['ok' => false, 'message' => $res['message']];
            }
            // Yuborib bo'lmadi (bot bloklangan) — qayta ulaymiz
        }

        $token = Str::random(24);
        OtpTelegramRequest::where('phone', $phone)->whereIn('state', ['pending', 'awaiting_contact'])->update(['state' => 'failed', 'note' => 'yangisi bilan almashtirildi']);
        OtpTelegramRequest::create([
            'phone' => $phone, 'purpose' => $purpose, 'token_hash' => hash('sha256', $token),
            'ip' => $ip, 'expires_at' => now()->addMinutes(self::REQUEST_MINUTES),
        ]);

        return ['ok' => true, 'sent' => false, 'token' => $token, 'link' => static::startLink('o_'.$token),
            'message' => __("Telegramni oching, «Start» va «Raqamni ulashish» tugmalarini bosing — kod o'sha yerga keladi.")];
    }

    /** Saytning holat so'rovi (polling). */
    public static function status(string $token): array
    {
        $req = static::find($token, true);
        if (! $req) {
            return ['state' => 'expired', 'message' => __("Havola eskirgan. Qayta urinib ko'ring.")];
        }

        return match ($req->state) {
            'sent' => ['state' => 'sent', 'message' => __('Kod Telegramga yuborildi. Uni pastdagi maydonga kiriting.')],
            'failed' => ['state' => 'failed', 'message' => $req->note ?: __("Kod yuborilmadi. Qayta urinib ko'ring.")],
            'awaiting_contact' => ['state' => 'awaiting_contact', 'message' => __('Botda «Raqamni ulashish» tugmasini bosing.')],
            default => ['state' => 'pending', 'message' => __('Telegramda botni oching va «Start» bosing.')],
        };
    }

    /* ---------------- Bot (webhook) tomoni ---------------- */

    public static function handleUpdate(array $update): void
    {
        $api = static::api();
        $m = $update['message'] ?? null;
        if (! $api || ! is_array($m) || ($m['chat']['type'] ?? '') !== 'private') {
            return;
        }
        $chat = (int) $m['chat']['id'];

        if (isset($m['contact'])) {
            static::handleContact($api, $m);

            return;
        }
        $text = trim((string) ($m['text'] ?? ''));
        if (preg_match('/^\/start(?:@\w+)?\s+o_([A-Za-z0-9]{10,40})$/', $text, $mm)) {
            static::handleStart($api, $mm[1], $chat);

            return;
        }
        if (str_starts_with($text, '/start')) {
            $api->send($chat, __(':app tasdiqlash boti.', ['app' => BillingService::appName()])."\n\n".__("Kod olish uchun saytda «Telegram orqali tasdiqlash» tugmasini bosing."));

            return;
        }
        if (OtpTelegramRequest::where('chat_id', $chat)->where('state', 'awaiting_contact')->where('expires_at', '>', now())->exists()) {
            $api->send($chat, __('Kod olish uchun pastdagi «📱 Raqamni ulashish» tugmasini bosing.'));
        } else {
            $api->send($chat, __("Kod olish uchun saytda «Telegram orqali tasdiqlash» tugmasini bosing."));
        }
    }

    protected static function handleStart(TelegramApi $api, string $token, int $chat): void
    {
        $req = static::find($token);
        if (! $req) {
            $api->send($chat, __("Havola eskirgan yoki ishlatilgan. Saytda «Telegram orqali tasdiqlash» tugmasini qayta bosing."));

            return;
        }
        if (static::knownChat($req->phone) === $chat) {
            static::finish($api, $req, $chat);

            return;
        }
        $req->update(['chat_id' => $chat, 'state' => 'awaiting_contact']);
        $api->send($chat, __(':app tasdiqlash kodi.', ['app' => BillingService::appName()])."\n\n"
            .__("Raqamingiz :p ekanini tasdiqlash uchun pastdagi tugmani bosing va o'z raqamingizni ulashing.", ['p' => static::mask($req->phone)]), [
            'reply_markup' => ['keyboard' => [[['text' => '📱 '.__('Raqamni ulashish'), 'request_contact' => true]]], 'resize_keyboard' => true, 'one_time_keyboard' => true],
        ]);
    }

    protected static function handleContact(TelegramApi $api, array $m): void
    {
        $chat = (int) $m['chat']['id'];
        $contact = (array) $m['contact'];
        $fromId = (int) ($m['from']['id'] ?? 0);
        $remove = ['reply_markup' => ['remove_keyboard' => true]];

        $req = OtpTelegramRequest::where('chat_id', $chat)->where('state', 'awaiting_contact')->where('expires_at', '>', now())->latest('id')->first();
        if (! $req) {
            $api->send($chat, __("Faol so'rov topilmadi. Saytda «Telegram orqali tasdiqlash» tugmasini bosing."), $remove);

            return;
        }
        // Faqat o'zining kontakti
        if ($fromId === 0 || (int) ($contact['user_id'] ?? 0) !== $fromId) {
            $api->send($chat, __("Faqat o'zingizning raqamingizni ulashing: pastdagi «Raqamni ulashish» tugmasini bosing."));

            return;
        }
        if (SmsService::normalize((string) ($contact['phone_number'] ?? '')) !== $req->phone) {
            $req->update(['state' => 'failed', 'note' => __('Telegramdagi raqam kiritilgan raqamga mos kelmadi.')]);
            $api->send($chat, __("Telegramdagi raqamingiz saytda kiritilgan raqamga mos kelmadi. Saytda to'g'ri raqamni kiriting va qayta urinib ko'ring."), $remove);

            return;
        }
        OtpTelegramLink::updateOrCreate(['phone' => $req->phone], [
            'chat_id' => $chat, 'tg_user_id' => $fromId,
            'username' => isset($m['from']['username']) ? mb_substr((string) $m['from']['username'], 0, 64) : null,
            'verified_at' => now(),
        ]);
        static::finish($api, $req, $chat);
    }

    protected static function finish(TelegramApi $api, OtpTelegramRequest $req, int $chat): void
    {
        $res = static::deliver($req->phone, $req->purpose, $chat);
        if ($res['ok']) {
            $req->update(['state' => 'sent', 'note' => null]);

            return;
        }
        $req->update(['state' => 'failed', 'note' => mb_substr($res['message'], 0, 250)]);
        $api->send($chat, $res['message'], ['reply_markup' => ['remove_keyboard' => true]]);
    }

    /** @return array{ok:bool,message:string,final?:bool} */
    protected static function deliver(string $phone, string $purpose, int $chat): array
    {
        $api = static::api();
        if (! $api) {
            return ['ok' => false, 'message' => __('Telegram bot sozlanmagan.'), 'final' => true];
        }
        if ($purpose === 'register' && (User::where('phone', $phone)->exists() || Clinic::where('phone', $phone)->exists())) {
            return ['ok' => false, 'final' => true, 'message' => __("Bu raqam bilan allaqachon ro'yxatdan o'tilgan.")];
        }
        if ($purpose === 'reset' && ! static::resetUser($phone)) {
            return ['ok' => false, 'final' => true, 'message' => __('Bu raqam bilan faol hisob topilmadi.')];
        }

        $code = OtpCodes::issue($phone, $purpose, 'telegram');
        $r = $api->send($chat, '🔐 '.__('Tasdiqlash kodi: :code', ['code' => $code])."\n\n"
            .__('Kod :n daqiqa amal qiladi. Uni hech kimga bermang.', ['n' => OtpCodes::TTL_MINUTES])
            ."\n".BillingService::appName(), ['protect_content' => true, 'reply_markup' => ['remove_keyboard' => true]]);
        if (! ($r['ok'] ?? false)) {
            OtpTelegramLink::where('phone', $phone)->where('chat_id', $chat)->delete(); // bot bloklangan bo'lishi mumkin
            \Illuminate\Support\Facades\DB::table('sms_codes')->where('phone', $phone)->where('purpose', $purpose)->delete();

            return ['ok' => false, 'message' => __("Kodni Telegramga yuborib bo'lmadi. Botni qayta ishga tushiring (/start) va urinib ko'ring.")];
        }

        return ['ok' => true, 'message' => 'ok'];
    }

    public static function resetUser(string $phone): ?User
    {
        return User::where('phone', $phone)->where('is_active', true)->first();
    }

    protected static function knownChat(string $phone): ?int
    {
        $l = OtpTelegramLink::where('phone', $phone)->where('verified_at', '>', now()->subDays(self::LINK_DAYS))->first();

        return $l ? (int) $l->chat_id : null;
    }

    protected static function find(string $token, bool $finished = false): ?OtpTelegramRequest
    {
        $q = OtpTelegramRequest::where('token_hash', hash('sha256', $token))->where('expires_at', '>', now());
        if (! $finished) {
            $q->whereIn('state', ['pending', 'awaiting_contact']);
        }

        return $q->first();
    }

    protected static function mask(string $phone): string
    {
        return '+'.substr($phone, 0, 3).' ** *** ** '.substr($phone, -2);
    }
}
