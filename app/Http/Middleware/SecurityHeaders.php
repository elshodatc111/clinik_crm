<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Xavfsizlik sarlavhalari (clickjacking, MIME-sniffing, CSP, HSTS) va shaxsiy sahifalarni keshlamaslik. */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if (! method_exists($response, 'headers') && ! isset($response->headers)) {
            return $response;
        }
        $h = $response->headers;
        $h->set('X-Content-Type-Options', 'nosniff');
        $h->set('X-Frame-Options', 'SAMEORIGIN');
        $h->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $h->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $h->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
        if ($request->isSecure()) {
            $h->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $mode = strtolower((string) env('SECURITY_CSP', app()->environment('production') ? 'enforce' : 'report'));
        if ($mode !== 'off') {
            $h->set($mode === 'enforce' ? 'Content-Security-Policy' : 'Content-Security-Policy-Report-Only', static::csp());
        }

        // Kirgan foydalanuvchining HTML sahifalari brauzer/proksi keshida qolmasin (chiqqandan keyin "orqaga" bosilsa ko'rinmasin)
        if ($request->user() && str_contains((string) $h->get('Content-Type'), 'text/html')) {
            $h->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }

    public static function csp(): string
    {
        // Tailwind CDN va Alpine ishlashi uchun 'unsafe-inline' / 'unsafe-eval' kerak
        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net",
            "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "img-src 'self' data: blob: https:",
            "connect-src 'self' https://cdn.tailwindcss.com",
            "frame-src https:",
            "form-action 'self' https://checkout.paycom.uz https://test.paycom.uz",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ]);
    }
}
