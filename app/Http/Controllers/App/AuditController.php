<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\Tenant;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $r)
    {
        $logs = AuditLog::with('user')->where('clinic_id', Tenant::id())->latest('id')
            ->when($r->query('type'), fn ($q, $t) => $q->where('auditable_type', $t))
            ->paginate(40)->withQueryString();

        return view('app.audit', ['logs' => $logs, 'showClinic' => false]);
    }
}
