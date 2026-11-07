<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Cloudflare → Nginx Proxy Manager → konteyner zanjirida so'rovning REMOTE_ADDR'i proksi (ichki) manzil bo'ladi:
 * barcha foydalanuvchilar bitta IP'ga aylanib, tezlik cheklovlari (throttle, RateLimiter) hammaga birdek ishlab qoladi.
 * Haqiqiy mijoz IP'si CF-Connecting-IP sarlavhasidan olinadi — faqat so'rov ichki (proksi) manzildan kelgandagina.
 */
class RealClientIp
{
    public function handle(Request $request, Closure $next)
    {
        $remote = (string) $request->server->get('REMOTE_ADDR');
        $cf = (string) $request->headers->get('CF-Connecting-IP');
        $fromProxy = filter_var($remote, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;

        if ($cf !== '' && $fromProxy && filter_var($cf, FILTER_VALIDATE_IP)) {
            $request->server->set('REMOTE_ADDR', $cf);
        }

        return $next($request);
    }
}
