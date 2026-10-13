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
        ]);
    }

    public function store(Request $r)
    {
        $d = $r->validate(['title' => 'required|string|max:150', 'body' => 'required|string']);
        Offer::query()->update(['is_active' => false]);
        Offer::create($d + ['version' => (int) Offer::max('version') + 1, 'is_active' => true]);

        return back()->with('success', __('Yangi oferta versiyasi faollashtirildi.'));
    }

    public function activate(Offer $offer)
    {
        Offer::query()->update(['is_active' => false]);
        $offer->update(['is_active' => true]);

        return back()->with('success', __('Oferta faollashtirildi.'));
    }
}
