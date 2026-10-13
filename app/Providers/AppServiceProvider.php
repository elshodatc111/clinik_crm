<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once app_path('Support/helpers.php');
    }

    public function boot(): void
    {
        config(['app.timezone' => 'Asia/Tashkent']);
        date_default_timezone_set('Asia/Tashkent');

        View::composer('*', function ($view) {
            static $brand = null;
            if ($brand === null) {
                try {
                    $color = Setting::get('primary_color', '#1d4ed8');
                    $logo = Setting::get('logo_path');
                    $brand = [
                        'name' => Setting::get('app_name', 'Klinika CRM'),
                        'color' => $color,
                        'dark' => self::darken($color),
                        'logo' => $logo ? Storage::disk('public')->url($logo) : null,
                        'phone' => Setting::get('support_phone'),
                        'email' => Setting::get('support_email'),
                        'contact' => [
                            'phone' => Setting::get('support_phone'), 'phone2' => Setting::get('support_phone2'), 'email' => Setting::get('support_email'),
                            'address' => Setting::get('contact_address'), 'hours' => Setting::get('contact_hours'),
                            'telegram' => Setting::get('contact_telegram'), 'instagram' => Setting::get('contact_instagram'),
                            'map' => Setting::get('contact_map_url'),
                        ],
                    ];
                } catch (\Throwable $e) {
                    $brand = ['name' => 'Klinika CRM', 'color' => '#1d4ed8', 'dark' => '#1e40af', 'logo' => null, 'phone' => null, 'email' => null, 'contact' => []];
                }
            }
            $view->with('brand', $brand);
        });
    }

    public static function darken(string $hex, float $f = 0.15): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return '#1e40af';
        }
        $rgb = array_map(fn ($c) => max(0, (int) round(hexdec($c) * (1 - $f))), str_split($hex, 2));

        return sprintf('#%02x%02x%02x', ...$rgb);
    }
}
