<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\SmsLog;
use App\Support\Tenant;

class SmsController extends Controller
{
    public function index()
    {
        return view('app.sms', ['logs' => SmsLog::where('clinic_id', Tenant::id())->latest('id')->paginate(40), 'showClinic' => false]);
    }
}
