<?php

namespace App\Http\Controllers;

/** Ilovani telefon/kompyuterga o'rnatish (PWA): manifest va oflayn sahifa. */
class PwaController extends Controller
{
    public function manifest()
    {
        $b = ['name' => \App\Models\Setting::get('app_name', 'Klinika CRM'), 'color' => \App\Models\Setting::get('primary_color', '#1d4ed8')];
        $data = [
            'name' => $b['name'], 'short_name' => mb_substr($b['name'], 0, 12), 'description' => __('Klinika boshqaruv tizimi'),
            'id' => '/app', 'start_url' => '/app?source=pwa', 'scope' => '/', 'display' => 'standalone', 'orientation' => 'any',
            'background_color' => '#f4f6fb', 'theme_color' => $b['color'], 'lang' => app()->getLocale(),
            'icons' => [
                ['src' => '/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
            'shortcuts' => [
                ['name' => __('Bemorlar'), 'url' => '/app/patients', 'icons' => [['src' => '/icons/icon-192.png', 'sizes' => '192x192']]],
                ['name' => __('Qabul kalendari'), 'url' => '/app/calendar', 'icons' => [['src' => '/icons/icon-192.png', 'sizes' => '192x192']]],
            ],
        ];

        return response()->json($data)->header('Content-Type', 'application/manifest+json');
    }

    public function offline()
    {
        return view('offline');
    }
}
