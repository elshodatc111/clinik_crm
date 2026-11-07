<?php

namespace App\Http\Controllers;

class SeoController extends Controller
{
    /** Ommaviy (indekslanadigan) sahifalar. */
    public static function pages(): array
    {
        return [
            ['route' => 'landing', 'priority' => '1.0', 'freq' => 'weekly'],
            ['route' => 'offer', 'priority' => '0.4', 'freq' => 'monthly'],
            ['route' => 'privacy', 'priority' => '0.4', 'freq' => 'monthly'],
        ];
    }

    public function robots()
    {
        $lines = [
            'User-agent: *',
            'Allow: /$',
            'Allow: /offer',
            'Allow: /privacy',
            'Disallow: /admin',
            'Disallow: /app',
            'Disallow: /account',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /forgot',
            'Disallow: /k/',
            'Disallow: /telegram',
            'Disallow: /payme',
            'Disallow: /lang/',
            '',
            'Sitemap: '.url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap()
    {
        $now = now()->toDateString();
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach (static::pages() as $p) {
            $loc = $p['route'] === 'landing' ? url('/') : route($p['route']);
            $xml .= '  <url><loc>'.htmlspecialchars($loc, ENT_XML1).'</loc><lastmod>'.$now.'</lastmod><changefreq>'.$p['freq'].'</changefreq><priority>'.$p['priority'].'</priority></url>'."\n";
        }

        return response($xml.'</urlset>'."\n", 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
