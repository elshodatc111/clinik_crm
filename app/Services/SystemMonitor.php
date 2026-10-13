<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tizim holati: so'rovlar statistikasi (5 daqiqalik paketlar), xatolar, sekin so'rovlar va server salomatligi.
 * Monitoring hech qachon asosiy ishni buzmasligi uchun barcha yozuvlar try/catch ichida.
 */
class SystemMonitor
{
    public const SLOW_MS = 1500;

    public static function record(Request $request, $response): void
    {
        try {
            $t0 = $request->attributes->get('_t0');
            if (! $t0 || $request->is('up', 'admin/system*')) {
                return;
            }
            $ms = (int) round((microtime(true) - $t0) * 1000);
            $status = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 200;
            $route = $request->route();
            $name = $route ? ($route->getName() ?: $route->uri()) : 'unknown';
            $name = mb_substr($name, 0, 120);
            $clinic = (int) (\App\Support\Tenant::id() ?: 0);
            $bucket = now()->setSecond(0)->setMinute(intdiv((int) now()->minute, 5) * 5)->format('Y-m-d H:i:s');
            $err = $status >= 500 ? 1 : 0;

            if (DB::getDriverName() === 'mysql') {
                DB::statement(
                    'INSERT INTO request_metrics (bucket, route, clinic_id, hits, errors, total_ms, max_ms) VALUES (?, ?, ?, 1, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE hits = hits + 1, errors = errors + VALUES(errors), total_ms = total_ms + VALUES(total_ms), max_ms = GREATEST(max_ms, VALUES(max_ms))',
                    [$bucket, $name, $clinic, $err, $ms, $ms]
                );
            } else {
                $row = DB::table('request_metrics')->where(['bucket' => $bucket, 'route' => $name, 'clinic_id' => $clinic]);
                if ($row->exists()) {
                    $row->update(['hits' => DB::raw('hits + 1'), 'errors' => DB::raw('errors + '.$err), 'total_ms' => DB::raw('total_ms + '.$ms), 'max_ms' => DB::raw('MAX(max_ms, '.$ms.')')]);
                } else {
                    DB::table('request_metrics')->insert(['bucket' => $bucket, 'route' => $name, 'clinic_id' => $clinic, 'hits' => 1, 'errors' => $err, 'total_ms' => $ms, 'max_ms' => $ms]);
                }
            }

            if ($ms >= self::SLOW_MS) {
                DB::table('system_errors')->insert([
                    'kind' => 'slow', 'route' => $name, 'method' => $request->method(), 'status' => $status, 'duration_ms' => $ms,
                    'clinic_id' => $clinic ?: null, 'user_id' => auth()->id(), 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            if (random_int(1, 300) === 1) {
                static::prune();
            }
        } catch (\Throwable $e) {
            // monitoring xatosi foydalanuvchiga ta'sir qilmasin
        }
    }

    public static function exception(\Throwable $e): void
    {
        try {
            if (! Schema::hasTable('system_errors')) {
                return;
            }
            $req = app()->bound('request') ? request() : null;
            DB::table('system_errors')->insert([
                'kind' => 'exception', 'route' => $req?->route()?->getName() ?: $req?->path(), 'method' => $req?->method(),
                'status' => 500, 'clinic_id' => \App\Support\Tenant::id(), 'user_id' => auth()->id(),
                'message' => mb_substr(class_basename($e).': '.$e->getMessage(), 0, 500),
                'file' => mb_substr(str_replace(base_path().DIRECTORY_SEPARATOR, '', $e->getFile()).':'.$e->getLine(), 0, 255),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        } catch (\Throwable $x) {
        }
    }

    public static function prune(): void
    {
        DB::table('request_metrics')->where('bucket', '<', now()->subDays(8))->delete();
        DB::table('system_errors')->where('created_at', '<', now()->subDays(30))->delete();
    }

    /** Server salomatligi tekshiruvlari. Har biri: [key,label,status(ok|warn|bad),value,hint] */
    public static function checks(): array
    {
        $out = [];

        // Ma'lumotlar bazasi
        try {
            $t = microtime(true);
            DB::select('select 1');
            $ms = round((microtime(true) - $t) * 1000, 1);
            $out[] = ['db', "Ma'lumotlar bazasi", $ms < 50 ? 'ok' : ($ms < 200 ? 'warn' : 'bad'), $ms.' ms', DB::getDriverName()];
        } catch (\Throwable $e) {
            $out[] = ['db', "Ma'lumotlar bazasi", 'bad', 'Ulanmadi', mb_substr($e->getMessage(), 0, 80)];
        }

        // Kesh
        try {
            $t = microtime(true);
            Cache::put('_health', 1, 10);
            $ok = Cache::get('_health') === 1;
            $ms = round((microtime(true) - $t) * 1000, 1);
            $out[] = ['cache', 'Kesh', $ok ? 'ok' : 'bad', $ok ? $ms.' ms' : 'Ishlamayapti', config('cache.default')];
        } catch (\Throwable $e) {
            $out[] = ['cache', 'Kesh', 'bad', 'Xato', ''];
        }

        // Disk
        $path = base_path();
        $free = @disk_free_space($path);
        $total = @disk_total_space($path);
        if ($free && $total) {
            $pct = round($free / $total * 100);
            $out[] = ['disk', 'Disk (bo\'sh joy)', $pct > 20 ? 'ok' : ($pct > 10 ? 'warn' : 'bad'), $pct.'%', static::bytes($free).' / '.static::bytes($total)];
        }
        $out[] = ['storage', 'Fayl saqlash (storage)', is_writable(storage_path()) ? 'ok' : 'bad', is_writable(storage_path()) ? 'Yoziladi' : 'Yozib bo\'lmaydi', ''];

        // Server yuklamasi
        if (function_exists('sys_getloadavg') && ($l = @sys_getloadavg())) {
            $out[] = ['load', 'Server yuklamasi (1/5/15 daq)', $l[0] < 2 ? 'ok' : ($l[0] < 4 ? 'warn' : 'bad'), round($l[0], 2).' / '.round($l[1], 2).' / '.round($l[2], 2), ''];
        }

        // PHP xotira
        $limit = static::iniBytes((string) ini_get('memory_limit'));
        $peak = memory_get_peak_usage(true);
        $out[] = ['mem', 'PHP xotira (shu so\'rov)', $limit > 0 && $peak / $limit > 0.8 ? 'warn' : 'ok', static::bytes($peak), 'limit: '.ini_get('memory_limit')];
        $op = function_exists('opcache_get_status') && ini_get('opcache.enable');
        $out[] = ['opcache', 'OPcache', $op ? 'ok' : 'warn', $op ? 'Yoqilgan' : "O'chiq", $op ? '' : 'Yoqilsa, sayt tezroq ishlaydi'];

        // Navbat
        try {
            if (config('queue.default') !== 'sync' && Schema::hasTable('jobs')) {
                $pending = DB::table('jobs')->count();
                $failed = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
                $out[] = ['queue', 'Navbat (queue)', $failed > 0 ? 'warn' : ($pending > 200 ? 'warn' : 'ok'), $pending.' kutmoqda', $failed.' xato'];
            }
        } catch (\Throwable $e) {
        }

        return $out;
    }

    /** So'nggi N soat bo'yicha umumiy ko'rsatkichlar, soatlik grafik, sekin marshrutlar, yuklamali klinikalar. */
    public static function stats(int $hours = 24): array
    {
        $from = now()->subHours($hours);
        $base = DB::table('request_metrics')->where('bucket', '>=', $from);

        $tot = (clone $base)->selectRaw('COALESCE(SUM(hits),0) h, COALESCE(SUM(errors),0) e, COALESCE(SUM(total_ms),0) t, COALESCE(MAX(max_ms),0) m')->first();
        $hits = (int) $tot->h;

        $rows = (clone $base)->selectRaw('bucket, SUM(hits) h, SUM(errors) e, SUM(total_ms) t')->groupBy('bucket')->orderBy('bucket')->get();
        $byHour = [];
        foreach ($rows as $r) {
            $k = substr((string) $r->bucket, 0, 13);
            $byHour[$k] ??= ['h' => 0, 'e' => 0, 't' => 0];
            $byHour[$k]['h'] += $r->h;
            $byHour[$k]['e'] += $r->e;
            $byHour[$k]['t'] += $r->t;
        }
        $labels = $reqs = $avgs = $errs = [];
        for ($i = $hours - 1; $i >= 0; $i--) {
            $d = now()->subHours($i);
            $k = $d->format('Y-m-d H');
            $labels[] = $d->format('H:00');
            $x = $byHour[$k] ?? null;
            $reqs[] = $x ? (int) $x['h'] : 0;
            $errs[] = $x ? (int) $x['e'] : 0;
            $avgs[] = $x && $x['h'] ? (int) round($x['t'] / $x['h']) : 0;
        }

        $slowRoutes = (clone $base)->selectRaw('route, SUM(hits) h, SUM(errors) e, SUM(total_ms)/SUM(hits) avg_ms, MAX(max_ms) mx')
            ->groupBy('route')->havingRaw('SUM(hits) >= 3')->orderByDesc('avg_ms')->limit(8)->get();
        $busyRoutes = (clone $base)->selectRaw('route, SUM(hits) h')->groupBy('route')->orderByDesc('h')->limit(8)->get();

        $clinics = (clone $base)->where('clinic_id', '>', 0)->selectRaw('clinic_id, SUM(hits) h, SUM(total_ms)/SUM(hits) avg_ms')
            ->groupBy('clinic_id')->orderByDesc('h')->limit(8)->get();
        $names = \App\Models\Clinic::whereIn('id', $clinics->pluck('clinic_id'))->pluck('name', 'id');

        $last5 = (int) DB::table('request_metrics')->where('bucket', '>=', now()->subMinutes(10))->sum('hits');
        $peak = DB::table('request_metrics')->where('bucket', '>=', $from)->selectRaw('bucket, SUM(hits) h')->groupBy('bucket')->orderByDesc('h')->first();

        return [
            'hits' => $hits, 'errors' => (int) $tot->e, 'avg' => $hits ? (int) round($tot->t / $hits) : 0, 'max' => (int) $tot->m,
            'errorRate' => $hits ? round($tot->e / $hits * 100, 2) : 0,
            'rpm' => round($last5 / 10, 1),
            'peakRpm' => $peak ? round($peak->h / 5, 1) : 0,
            'labels' => $labels, 'reqs' => $reqs, 'avgs' => $avgs, 'errs' => $errs,
            'slowRoutes' => $slowRoutes, 'busyRoutes' => $busyRoutes, 'clinics' => $clinics, 'names' => $names,
        ];
    }

    /** Jadval hajmlari va DB ulanishlari (faqat MySQL). */
    public static function database(): array
    {
        if (DB::getDriverName() !== 'mysql') {
            return ['tables' => collect(), 'threads' => null, 'size' => null];
        }
        try {
            $tables = collect(DB::select('SELECT table_name AS n, table_rows AS r, ROUND((data_length+index_length)/1048576,2) AS mb FROM information_schema.tables WHERE table_schema = DATABASE() ORDER BY (data_length+index_length) DESC LIMIT 8'));
            $threads = DB::selectOne("SHOW STATUS LIKE 'Threads_connected'")->Value ?? null;

            return ['tables' => $tables, 'threads' => $threads, 'size' => round($tables->sum('mb'), 1)];
        } catch (\Throwable $e) {
            return ['tables' => collect(), 'threads' => null, 'size' => null];
        }
    }

    /** Biznes darajasidagi nosozliklar (24 soat). */
    public static function business(): array
    {
        $since = now()->subDay();
        $q = fn ($t, $w) => DB::table($t)->where('created_at', '>=', $since)->where($w)->count();

        return [
            'sms_failed' => $q('sms_logs', ['status' => 'failed']),
            'sms_total' => DB::table('sms_logs')->where('created_at', '>=', $since)->count(),
            'booking_pending' => DB::table('online_bookings')->where('status', 'pending')->where('hold_expires_at', '>', now())->count(),
            'booking_expired' => $q('online_bookings', ['status' => 'expired']),
            'clinics_block' => DB::table('clinics')->whereIn('status', ['blocked', 'view_only'])->count(),
        ];
    }

    /** Avtomatik ogohlantirishlar. */
    public static function alerts(array $checks, array $s, array $biz): array
    {
        $a = [];
        foreach ($checks as [$k, $label, $st, $val]) {
            if ($st === 'bad') {
                $a[] = ['bad', $label.': '.$val];
            } elseif ($st === 'warn') {
                $a[] = ['warn', $label.': '.$val];
            }
        }
        if ($s['hits'] >= 50 && $s['errorRate'] > 2) {
            $a[] = ['bad', "Xatolar ulushi yuqori: {$s['errorRate']}%"];
        }
        if ($s['hits'] >= 50 && $s['avg'] > 800) {
            $a[] = ['warn', "O'rtacha javob vaqti sekin: {$s['avg']} ms"];
        }
        if ($biz['sms_total'] >= 10 && $biz['sms_failed'] / $biz['sms_total'] > 0.2) {
            $a[] = ['warn', "SMS yuborishda xatolar ko'p: {$biz['sms_failed']} / {$biz['sms_total']}"];
        }

        return $a;
    }

    public static function bytes($b): string
    {
        $u = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $b = (float) $b;
        while ($b >= 1024 && $i < 4) {
            $b /= 1024;
            $i++;
        }

        return round($b, 1).' '.$u[$i];
    }

    protected static function iniBytes(string $v): int
    {
        $v = trim($v);
        if ($v === '' || $v === '-1') {
            return 0;
        }
        $n = (int) $v;

        return match (strtolower(substr($v, -1))) {'g' => $n * 1073741824, 'm' => $n * 1048576, 'k' => $n * 1024, default => $n};
    }
}
