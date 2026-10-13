<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\OfferAcceptance;
use Illuminate\Http\Request;

/** Xodim tizimga kirgach ofertani o'qib, qabul qiladi (birinchi marta va har bir yangi versiyada). */
class OfferAcceptController extends Controller
{
    public function show(Request $r)
    {
        $offer = Offer::active();
        $u = $r->user();
        if ($u->isSuper() || ! $offer || $offer->acceptedBy($u)) {
            return redirect()->route('dashboard');
        }

        return view('legal.accept', ['offer' => $offer, 'text' => $offer->text(), 'reaccept' => OfferAcceptance::where('user_id', $u->id)->exists()]);
    }

    public function accept(Request $r)
    {
        $r->validate(['accept_offer' => 'accepted', 'offer_id' => 'required|integer']);
        $offer = Offer::active();
        $u = $r->user();
        abort_unless($offer && (int) $r->input('offer_id') === $offer->id, 409);

        if (! $offer->acceptedBy($u)) {
            OfferAcceptance::create([
                'clinic_id' => $u->clinic_id,
                'user_id' => $u->id,
                'context' => 'login',
                'clinic_name' => $u->clinic?->name ?? '—',
                'user_name' => $u->name,
                'phone' => $u->phone ?: ($u->email ?: '—'),
                'offer_id' => $offer->id,
                'offer_version' => $offer->version,
                'ip_address' => $r->ip(),
                'user_agent' => mb_substr((string) $r->userAgent(), 0, 500),
                'accepted_at' => now(),
            ]);
        }
        $to = $r->session()->pull('intended_after_offer');

        return redirect()->to($to ?: route('dashboard'));
    }
}
