<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SystemMonitor;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class SystemController extends Controller
{
    public function index(Request $r)
    {
        $hours = in_array((int) $r->query('h'), [6, 24, 72, 168], true) ? (int) $r->query('h') : 24;
        $checks = SystemMonitor::checks();
        $stats = SystemMonitor::stats($hours);
        $biz = SystemMonitor::business();

        return view('admin.system', [
            'hours' => $hours, 'checks' => $checks, 'stats' => $stats, 'biz' => $biz,
            'alerts' => SystemMonitor::alerts($checks, $stats, $biz),
            'db' => SystemMonitor::database(),
            'sysErrors' => DB::table('system_errors')->where('kind', 'exception')->orderByDesc('id')->limit(15)->get(),
            'slow' => DB::table('system_errors')->where('kind', 'slow')->orderByDesc('id')->limit(10)->get(),
            'env' => ['php' => PHP_VERSION, 'laravel' => app()->version(), 'env' => app()->environment(), 'debug' => (bool) config('app.debug'), 'tz' => config('app.timezone')],
        ]);
    }

    public function clear()
    {
        DB::table('system_errors')->delete();

        return back()->with('success', __('Jurnal tozalandi.'));
    }
}
