<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $r)
    {
        $q = AuditLog::with(['user', 'clinic'])->latest('id');
        if ($c = $r->query('clinic_id')) {
            $q->where('clinic_id', $c);
        }
        if ($t = $r->query('type')) {
            $q->where('auditable_type', $t);
        }

        return view('app.audit', ['logs' => $q->paginate(40)->withQueryString(), 'showClinic' => true]);
    }
}
