<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\SmsLog;
use App\Support\SmsEvents;

/**
 * Platforma xabarlari (klinikalarga): ro'yxatdan o'tish kodi, obuna, to'lov, ogohlantirishlar.
 * Matnlar Super Admin → SMS matnlari bo'limida tahrirlanadi (Setting: platform_sms).
 */
class PlatformSms
{
    public static function all(): array
    {
        return [
            'otp' => [
                'label' => "Ro'yxatdan o'tish tasdiqlash kodi", 'icon' => 'lock', 'locked' => true,
                'when' => "Yangi klinika ro'yxatdan o'tishda telefonini tasdiqlayotganda. O'chirib bo'lmaydi, matnda {code} bo'lishi shart.",
                'vars' => ['code', 'app'], 'text' => 'Tasdiqlash kodi: {code}. {app}',
            ],
            'subscribed' => [
                'label' => 'Obuna faollashtirildi', 'icon' => 'check',
                'when' => "Klinika ulanish to'lovini to'lab, demodan obunaga o'tganda.",
                'vars' => ['app'], 'text' => "{app}: obuna faollashtirildi. Birinchi oy bepul. Rahmat!",
            ],
            'renewed' => [
                'label' => 'Obuna uzaytirildi', 'icon' => 'refresh',
                'when' => "Balans to'ldirilib, muddati o'tgan obuna avtomatik uzaytirilganda.",
                'vars' => ['app'], 'text' => '{app}: obuna uzaytirildi. Rahmat!',
            ],
            'topup_confirmed' => [
                'label' => "Karta to'lovi tasdiqlandi", 'icon' => 'credit-card',
                'when' => "Super Admin klinikaning karta to'lovi chekini tasdiqlaganda.",
                'vars' => ['amount', 'app'], 'text' => "{app}: to'lovingiz tasdiqlandi, balansga {amount} so'm qo'shildi. Rahmat!",
            ],
            'topup_rejected' => [
                'label' => "Karta to'lovi bekor qilindi", 'icon' => 'x',
                'when' => "Super Admin chekni bekor qilganda (sabab bilan).",
                'vars' => ['reason', 'app'], 'text' => "{app}: to'lov chekingiz qabul qilinmadi. Sabab: {reason}. Batafsil: ilovadagi Obuna bo'limi.",
            ],
            'trial_warn' => [
                'label' => 'Demo tugashiga 7, 3 va 1 kun qolganda', 'icon' => 'clock',
                'when' => "Demo davri tugashiga 7, 3 va 1 kun qolganda (har biri bir martadan).",
                'vars' => ['days', 'app'], 'text' => "{app}: demo versiya tugashiga {days} kun qoldi. Obunaga o'tmasangiz ma'lumotlar o'chiriladi.",
            ],
            'trial_purged' => [
                'label' => "Demo tugadi, ma'lumotlar o'chirildi", 'icon' => 'trash',
                'when' => "Demo tugab, obuna olinmagani uchun ma'lumotlar o'chirilganda.",
                'vars' => ['app'], 'text' => "{app}: demo muddati tugadi va obuna olinmagani uchun barcha ma'lumotlaringiz o'chirildi.",
            ],
            'low_balance' => [
                'label' => "Obuna tugashiga 3 kun qoldi, balans yetarli emas", 'icon' => 'wallet',
                'when' => "Obuna tugashiga 3 kun yoki kam qolib, balansda oylik to'lov uchun mablag' yetmasa.",
                'vars' => ['days', 'app'], 'text' => "{app}: obuna tugashiga {days} kun qoldi, balansda mablag' yetarli emas. Balansni to'ldiring.",
            ],
            'view_only' => [
                'label' => "Faqat ko'rish rejimiga o'tdi", 'icon' => 'eye',
                'when' => "Oylik to'lov to'lanmay, tizim faqat ko'rish rejimiga o'tganda.",
                'vars' => ['app'], 'text' => "{app}: oylik to'lov to'lanmadi. Tizim faqat ko'rish rejimiga o'tdi. Balansni to'ldiring.",
            ],
            'blocked' => [
                'label' => 'Klinika bloklandi', 'icon' => 'shield',
                'when' => "3-oy yakunigacha to'lanmay, klinika bloklanganda.",
                'vars' => ['app'], 'text' => "{app}: klinikangiz bloklandi. 30 kundan keyin barcha ma'lumotlaringiz o'chiriladi. To'lovni amalga oshiring.",
            ],
            'purge_warn' => [
                'label' => "Ma'lumotlar o'chirilishidan ogohlantirish (14, 7, 1 kun)", 'icon' => 'alert',
                'when' => "Bloklangan klinikaga ma'lumotlar o'chirilishiga 14, 7 va 1 kun qolganda.",
                'vars' => ['days', 'app'], 'text' => "{app}: {days} kundan keyin barcha ma'lumotlaringiz o'chiriladi. To'lovni amalga oshiring.",
            ],
            'purged' => [
                'label' => "Shartnoma bekor qilindi, ma'lumotlar o'chirildi", 'icon' => 'trash',
                'when' => "Bloklangandan 30 kun o'tib to'lanmaganda, ma'lumotlar o'chirilganda.",
                'vars' => ['app'], 'text' => "{app}: to'lov qilinmagani uchun shartnoma bekor qilindi va ma'lumotlaringiz o'chirildi.",
            ],
        ];
    }

    public static function sample(): array
    {
        return ['code' => '123456', 'app' => BillingService::appName(), 'amount' => '500 000', 'days' => '3', 'reason' => "chekda summa ko'rinmayapti"];
    }

    protected static function store(): array
    {
        $raw = Setting::get('platform_sms');

        return is_string($raw) ? (array) json_decode($raw, true) : [];
    }

    public static function get(string $event): array
    {
        $def = static::all()[$event] ?? null;
        $st = (array) (static::store()[$event] ?? []);
        $locked = (bool) ($def['locked'] ?? false);

        return [
            'text' => trim((string) ($st['text'] ?? '')) ?: ($def['text'] ?? ''),
            'enabled' => $locked ? true : (array_key_exists('enabled', $st) ? (bool) $st['enabled'] : true),
            'eskiz_id' => $st['eskiz_id'] ?? null,
            'status' => $st['status'] ?? 'draft',
            'approved_text' => $st['approved_text'] ?? null,
            'checked_at' => $st['checked_at'] ?? null,
            'note' => $st['note'] ?? null,
        ];
    }

    public static function patch(string $event, array $data): void
    {
        $all = static::store();
        $all[$event] = array_merge((array) ($all[$event] ?? []), $data);
        Setting::set('platform_sms', json_encode($all, JSON_UNESCAPED_UNICODE));
    }

    public static function reset(string $event): void
    {
        $all = static::store();
        unset($all[$event]);
        Setting::set('platform_sms', json_encode($all, JSON_UNESCAPED_UNICODE));
    }

    public static function globalEnabled(): bool
    {
        return Setting::get('platform_sms_off') !== '1';
    }

    public static function needApproval(): bool
    {
        return Setting::get('platform_sms_need_approval') === '1';
    }

    public static function render(string $event, array $vars = []): string
    {
        return SmsEvents::render(static::get($event)['text'], $vars + ['app' => BillingService::appName()]);
    }

    /** Platforma SMS ini yuborish (umumiy Eskiz akkaunti orqali). Yuborildimi — true. */
    public static function send(string $event, ?string $phone, array $vars = [], ?int $clinicId = null): bool
    {
        if (! $phone || ! isset(static::all()[$event])) {
            return false;
        }
        $tpl = static::get($event);
        $log = $event === 'otp' ? 'otp' : 'p_'.$event;
        $text = static::render($event, $vars);
        $skip = function (string $why) use ($phone, $text, $clinicId, $log, $event) {
            SmsLog::create(['clinic_id' => $clinicId, 'phone' => SmsService::normalize($phone), 'message' => $event === 'otp' ? preg_replace('/\d{6}/', '••••••', $text) : $text, 'status' => 'skipped', 'event' => $log, 'response' => $why]);

            return false;
        };
        if ($event !== 'otp') {
            if (! static::globalEnabled()) {
                return $skip("Platforma SMS xizmati o'chirilgan");
            }
            if (! $tpl['enabled']) {
                return $skip("Bu xabar turi o'chirilgan");
            }
            if (static::needApproval() && $tpl['status'] !== 'approved') {
                return $skip('Shablon Eskiz tomonidan tasdiqlanmagan ('.SmsEvents::statusLabel($tpl['status']).')');
            }
        }

        return SmsService::send($phone, $text, $clinicId, false, $log);
    }
}
