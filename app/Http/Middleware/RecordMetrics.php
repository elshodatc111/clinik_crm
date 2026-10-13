<?php

namespace App\Http\Middleware;

use App\Services\SystemMonitor;
use Closure;
use Illuminate\Http\Request;

/** Har bir so'rov davomiyligini yengil tarzda yozib boradi (superadmin "Tizim holati" uchun). */
class RecordMetrics
{
    public function handle(Request $request, Closure $next)
    {
        $request->attributes->set('_t0', microtime(true));

        return $next($request);
    }

    public function terminate(Request $request, $response): void
    {
        SystemMonitor::record($request, $response);
    }
}
