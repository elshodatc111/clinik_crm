<?php

namespace App\Http\Controllers;

use App\Services\PaymeService;
use Illuminate\Http\Request;

class PaymeController extends Controller
{
    public function handle(Request $request, PaymeService $payme)
    {
        return response()->json($payme->handle($request));
    }
}
