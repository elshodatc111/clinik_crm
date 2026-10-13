<?php

namespace App\Http\Middleware;

use App\Models\Offer;
use Closure;
use Illuminate\Http\Request;

/** Klinika xodimi ofertaning joriy versiyasini qabul qilmaguncha tizimdan foydalana olmaydi. */
class EnsureOfferAccepted
{
    public function handle(Request $request, Closure $next)
    {
        $u = $request->user();
        if (! $u || $u->isSuper() || ! config('legal.enforce_staff_offer')) {
            return $next($request);
        }
        $offer = Offer::active();
        if (! $offer || $offer->acceptedBy($u)) {
            return $next($request);
        }
        if ($request->expectsJson() || $request->is('notifications/feed', 'app/search')) {
            return response()->json(['message' => 'offer_required'], 409);
        }

        if ($request->isMethod('GET')) {
            session(['intended_after_offer' => $request->fullUrl()]);
        }

        return redirect()->route('offer.accept');
    }
}
