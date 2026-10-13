<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\OfferAcceptance;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    public function index()
    {
        return view('admin.offers', [
            'offers' => Offer::orderByDesc('version')->get(),
            'acceptances' => OfferAcceptance::orderByDesc('id')->limit(30)->get(),
            'staffAccepted' => OfferAcceptance::where('context', 'login')->distinct('user_id')->count('user_id'),
        ]);
    }

    public function store(Request $r)
    {
        $d = $r->validate(['title' => 'required|string|max:150', 'body' => 'required|string', 'body_ru' => 'nullable|string']);
        Offer::query()->update(['is_active' => false]);
        Offer::create($d + ['version' => (int) Offer::max('version') + 1, 'is_active' => true]);

        return back()->with('success', __('Yangi oferta versiyasi faollashtirildi.'));
    }

    public function privacy(Request $r)
    {
        $d = $r->validate(['privacy_uz' => 'nullable|string|max:60000', 'privacy_ru' => 'nullable|string|max:60000']);
        \App\Models\Setting::set('privacy_uz', $d['privacy_uz'] ?? null);
        \App\Models\Setting::set('privacy_ru', $d['privacy_ru'] ?? null);

        return back()->with('success', __('Maxfiylik siyosati saqlandi.'));
    }

    public function activate(Offer $offer)
    {
        Offer::query()->update(['is_active' => false]);
        $offer->update(['is_active' => true]);

        return back()->with('success', __('Oferta faollashtirildi.'));
    }
}
