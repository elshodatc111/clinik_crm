<?php

namespace App\Support;

use App\Models\Setting;

/** Oferta va maxfiylik siyosati matnlarini joylashtirish ({{APP}}, {{COMPANY}} ...). */
class Legal
{
    public static function vars(): array
    {
        $app = Setting::get('app_name', 'Klinika CRM');

        return [
            '{{APP}}' => $app,
            '{{COMPANY}}' => Setting::get('company_name') ?: $app.' (Ijrochi)',
            '{{DATE}}' => now()->format('d.m.Y'),
            '{{ADDRESS}}' => Setting::get('contact_address') ?: '—',
            '{{PHONE}}' => Setting::get('support_phone') ?: '—',
            '{{EMAIL}}' => Setting::get('support_email') ?: '—',
            '{{SITE}}' => rtrim((string) config('app.url'), '/'),
        ];
    }

    public static function render(?string $text, ?string $date = null): string
    {
        $v = static::vars();
        if ($date) {
            $v['{{DATE}}'] = $date;
        }

        return strtr((string) $text, $v);
    }

    /** Maxfiylik siyosati: Super Admin kiritgan matn yoki standart fayl. */
    public static function privacy(?string $locale = null): string
    {
        $locale = in_array($locale, ['uz', 'ru'], true) ? $locale : app()->getLocale();
        $custom = trim((string) Setting::get('privacy_'.$locale));
        if ($custom !== '') {
            return static::render($custom);
        }
        $file = resource_path('data/privacy_'.$locale.'.txt');
        if (! is_file($file)) {
            $file = resource_path('data/privacy_uz.txt');
        }

        return static::render((string) file_get_contents($file));
    }
}
