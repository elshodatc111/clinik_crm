<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsLog;

class SmsController extends Controller
{
    public function index()
    {
        return view('app.sms', ['logs' => SmsLog::latest('id')->paginate(40), 'showClinic' => true]);
    }
}
