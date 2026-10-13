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
            'tasks' => \App\Services\TaskMonitor::rows(), 'scheduler' => \App\Services\TaskMonitor::scheduler(),
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

    /** Xavfsiz vazifalarni qo'lda ishga tushirish (tekshirish uchun). */
    public function run(string $task)
    {
        $def = \App\Services\TaskMonitor::defs()[$task] ?? null;
        abort_unless($def && $def['run'], 404);
        $t0 = microtime(true);
        try {
            $code = \Illuminate\Support\Facades\Artisan::call($task);
            \App\Services\TaskMonitor::record($task, $code === 0, (int) round((microtime(true) - $t0) * 1000), $code === 0 ? null : 'Chiqish kodi: '.$code);

            return back()->with($code === 0 ? 'success' : 'error', $code === 0 ? __('Vazifa bajarildi: :n', ['n' => __($def['label'])]) : __('Vazifa xato bilan tugadi.'));
        } catch (\Throwable $e) {
            \App\Services\TaskMonitor::record($task, false, 0, $e->getMessage());

            return back()->with('error', __('Xato: :e', ['e' => mb_substr($e->getMessage(), 0, 200)]));
        }
    }
}
