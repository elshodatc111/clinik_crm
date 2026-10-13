<?php

namespace App\Support;

use App\Models\Clinic;

/** SMS hodisalari (qaysi holatda SMS yuboriladi) va ularning standart shablonlari. */
class SmsEvents
{
    public static function all(): array
    {
        return [
            'appt_created' => [
                'label' => 'Qabulga yozilganda', 'icon' => 'calendar',
                'when' => "Qabulxona yangi qabul yaratib, \"SMS yuborish\" belgisini qo'ysa.",
                'vars' => ['name', 'date', 'doctor', 'clinic', 'address'],
                'text' => 'Hurmatli {name}, {date} da {clinic} klinikasida {doctor} qabuliga yozildingiz. Manzil: {address}',
            ],
            'booking_confirmed' => [
                'label' => 'Onlayn qabul tasdiqlandi', 'icon' => 'credit-card',
                'when' => "Bemor onlayn sahifada to'lov qilgach darhol.",
                'vars' => ['name', 'date', 'doctor', 'n', 'clinic'],
                'text' => 'Hurmatli {name}, qabulingiz tasdiqlandi: {date}, {doctor}. Navbat raqami: {n}. {clinic}',
            ],
            'remind_24h' => [
                'label' => 'Qabuldan 1 kun oldin eslatma', 'icon' => 'clock',
                'when' => 'Qabulga taxminan 24 soat qolganda avtomatik.',
                'vars' => ['name', 'date', 'doctor', 'clinic', 'address'],
                'text' => 'Hurmatli {name}, ertaga {date} da {clinic} klinikasida {doctor} qabuliga yozilgansiz. Manzil: {address}',
            ],
            'remind_2h' => [
                'label' => 'Qabuldan 2 soat oldin eslatma', 'icon' => 'clock-3',
                'when' => 'Qabulga 2 soat qolganda avtomatik.',
                'vars' => ['name', 'date', 'doctor', 'clinic', 'address'],
                'text' => 'Hurmatli {name}, soat {date} da {doctor} qabuliga 2 soatdan keyin kutamiz. {clinic}',
            ],
            'lab_ready' => [
                'label' => 'Tahlil natijasi tayyor', 'icon' => 'flask',
                'when' => "Laborant natijani kiritib, \"bemorga xabar\" belgisini qo'ysa.",
                'vars' => ['name', 'clinic'],
                'text' => 'Hurmatli {name}, tahlil natijangiz tayyor. {clinic}',
            ],
        ];
    }

    public static function sample(): array
    {
        return ['name' => 'Alisher', 'date' => '15.10.2026 10:30', 'doctor' => 'Dr. Karimov', 'clinic' => 'Demo Klinika', 'address' => 'Toshkent, Chilonzor 12', 'n' => '3'];
    }

    /** Klinikaning shu hodisa bo'yicha holati (standartlar bilan birlashtirilgan). */
    public static function get(Clinic $c, string $event): array
    {
        $def = static::all()[$event] ?? null;
        $st = (array) $c->setting('sms_tpls.'.$event, []);
        $legacy = ['appt_created' => 'sms_on_create', 'remind_24h' => 'sms_remind_24h', 'remind_2h' => 'sms_remind_2h', 'lab_ready' => 'sms_lab_ready'][$event] ?? null;
        $legacyText = in_array($event, ['remind_24h', 'remind_2h'], true) ? $c->setting('sms_tpl_reminder') : null;

        return [
            'text' => trim((string) ($st['text'] ?? '')) ?: ($legacyText ? static::fromLegacy($legacyText) : ($def['text'] ?? '')),
            'enabled' => array_key_exists('enabled', $st) ? (bool) $st['enabled'] : (bool) ($legacy ? $c->setting($legacy, true) : true),
            'eskiz_id' => $st['eskiz_id'] ?? null,
            'status' => $st['status'] ?? 'draft',        // draft | moderation | approved | rejected
            'approved_text' => $st['approved_text'] ?? null,
            'checked_at' => $st['checked_at'] ?? null,
            'note' => $st['note'] ?? null,
        ];
    }

    public static function fromLegacy(string $t): string
    {
        return preg_replace('/:(name|date|clinic|doctor|address)\b/', '{$1}', $t);
    }

    public static function render(string $text, array $vars): string
    {
        $map = [];
        foreach ($vars as $k => $v) {
            $map['{'.$k.'}'] = (string) $v;
            $map[':'.$k] = (string) $v;
        }

        return trim(strtr($text, $map));
    }

    /** Jurnalda hodisa nomi: klinika hodisalari, platforma xabarlari (p_*), kod va sinov. */
    public static function label(?string $event): string
    {
        if (! $event) {
            return '';
        }
        if (isset(static::all()[$event])) {
            return __(static::all()[$event]['label']);
        }
        if ($event === 'otp') {
            return __('Tasdiqlash kodi');
        }
        if ($event === 'test') {
            return __('Sinov');
        }
        if (str_starts_with($event, 'p_') && isset(\App\Services\PlatformSms::all()[substr($event, 2)])) {
            return __(\App\Services\PlatformSms::all()[substr($event, 2)]['label']);
        }

        return $event;
    }

    public static function statusLabel(string $s): string
    {
        return ['draft' => 'Yuborilmagan', 'moderation' => 'Eskiz tekshirmoqda', 'approved' => 'Tasdiqlangan', 'rejected' => 'Rad etilgan'][$s] ?? $s;
    }
}
