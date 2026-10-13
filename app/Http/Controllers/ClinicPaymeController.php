<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Services\ClinicPaymeService;
use Illuminate\Http\Request;

/** Klinikaning o'z Payme kassasi uchun Merchant API manzili: /payme/c/{slug} */
class ClinicPaymeController extends Controller
{
    public function handle(Request $request, string $slug)
    {
        $clinic = Clinic::where('slug', $slug)->firstOrFail();

        return response()->json((new ClinicPaymeService($clinic))->handle($request));
    }
}
