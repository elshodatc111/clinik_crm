<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Support\Guides;
use Illuminate\Http\Request;

class GuideController extends Controller
{
    public function index(Request $r)
    {
        $guides = Guides::forUser($r->user());
        // Faqat bitta yo'riqnomasi bo'lgan xodim to'g'ridan-to'g'ri unga o'tadi
        if (count($guides) === 1) {
            return redirect()->route('app.guides.show', array_key_first($guides));
        }

        return view('app.guides.index', ['guides' => $guides]);
    }

    public function show(Request $r, string $key)
    {
        $guides = Guides::forUser($r->user());
        abort_unless(isset($guides[$key]), 404);

        return view('app.guides.show', ['key' => $key, 'guide' => $guides[$key], 'count' => count($guides)]);
    }
}
