<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Ommaviy bo'lmagan sahifalarni qidiruv tizimlaridan yashiradi (X-Robots-Tag). */
class RobotsTag
{
    private const PUBLIC = ['landing', 'offer', 'privacy', 'seo.robots', 'seo.sitemap'];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if (! $request->routeIs(...self::PUBLIC) && isset($response->headers)) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
