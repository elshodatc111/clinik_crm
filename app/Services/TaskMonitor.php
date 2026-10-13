<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Console\Scheduling\Event as ScheduledEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Fon jarayonlari (scheduler / cron) oxirgi ishga tushish vaqti va holati. */
class TaskMonitor
{
    /** name => [label, schedule matni, davr (soniya), qo'lda ishga tushirish mumkinmi] */
    public static function defs(): array
    {
        return [
            'bookings:expire' => ['label' => "Onlayn bronlarni bo'shatish", 'every' => 'Har daqiqa', 'period' => 60, 'run' => false],
            'reminders:send' => ['label' => 'Qabul eslatma SMS lari', 'every' => 'Har 10 daqiqa', 'period' => 600, 'run' => true],
            'billing:run' => ['label' => 'Obuna, demo va bloklash (billing)', 'every' => 'Har kuni 02:00', 'period' => 86400, 'run' => false],
            'db:backup' => ['label' => 'Bazani zaxiralash', 'every' => 'Har kuni 03:00', 'period' => 86400, 'run' => true],
            'clinics:diagnose' => ['label' => 'Klinikalar diagnostikasi', 'every' => 'Har kuni 09:00', 'period' => 86400, 'run' => true],
        ];
    }

    public static function nameOf(ScheduledEvent $e): ?string
    {
        if ($e->description) {
            return mb_substr($e->description, 0, 60);
        }
        if (is_string($e->command) && preg_match('/artisan[\'"]?\s+([a-z0-9:_-]+)/i', $e->command, $m)) {
            return $m[1];
        }

        return null;
    }

    public static function record(string $name, bool $ok, int $ms = 0, ?string $msg = null): void
    {
        try {
            if (! Schema::hasTable('task_heartbeats')) {
                return;
            }
            $now = now();
            $row = DB::table('task_heartbeats')->where('name', $name);
            if ($row->exists()) {
                $row->update([
                    'last_run_at' => $now, 'last_status' => $ok ? 'ok' : 'failed', 'last_message' => $msg ? mb_substr($msg, 0, 500) : null, 'last_ms' => $ms,
                    'runs' => DB::raw('runs + 1'), 'fails' => DB::raw('fails + '.($ok ? 0 : 1)),
                ] + ($ok ? [] : ['last_fail_at' => $now]));
            } else {
                DB::table('task_heartbeats')->insert([
                    'name' => $name, 'last_run_at' => $now, 'last_status' => $ok ? 'ok' : 'failed', 'last_message' => $msg ? mb_substr($msg, 0, 500) : null,
                    'last_ms' => $ms, 'runs' => 1, 'fails' => $ok ? 0 : 1, 'last_fail_at' => $ok ? null : $now,
                ]);
            }
        } catch (\Throwable $e) {
            // monitoring asosiy ishni buzmasin
        }
    }

    /** @return array<int,array> har bir vazifa uchun holat */
    public static function rows(): array
    {
        $beats = [];
        try {
            if (Schema::hasTable('task_heartbeats')) {
                $beats = DB::table('task_heartbeats')->get()->keyBy('name')->all();
            }
        } catch (\Throwable $e) {
        }
        $out = [];
        foreach (static::defs() as $name => $d) {
            $b = $beats[$name] ?? null;
            $last = $b?->last_run_at ? Carbon::parse($b->last_run_at) : null;
            $age = $last ? max(0, now()->getTimestamp() - $last->getTimestamp()) : null;
            $p = $d['period'];
            if ($b && $b->last_status === 'failed') {
                $st = 'bad';
                $note = 'Oxirgi ishga tushishda xato';
            } elseif ($age === null) {
                $st = $p >= 86400 ? 'warn' : 'bad';
                $note = 'Hali ishlamagan';
            } else {
                [$okMax, $warnMax] = $p >= 86400 ? [93600, 180000] : ($p >= 600 ? [1500, 3600] : [180, 600]);
                $st = $age <= $okMax ? 'ok' : ($age <= $warnMax ? 'warn' : 'bad');
                $note = $st === 'ok' ? 'Ishlayapti' : 'Kechikmoqda';
            }
            $out[] = $d + ['name' => $name, 'status' => $st, 'note' => $note, 'last' => $last, 'age' => $age, 'ms' => (int) ($b->last_ms ?? 0),
                'runs' => (int) ($b->runs ?? 0), 'fails' => (int) ($b->fails ?? 0), 'message' => $b->last_message ?? null];
        }

        return $out;
    }

    /** Scheduler (cron) umumiy holati: har daqiqa ishlaydigan vazifadan aniqlanadi. */
    public static function scheduler(): array
    {
        $row = collect(static::rows())->firstWhere('name', 'bookings:expire');
        $age = $row['age'] ?? null;
        $st = $age === null ? 'bad' : ($age <= 180 ? 'ok' : ($age <= 600 ? 'warn' : 'bad'));

        return ['status' => $st, 'age' => $age, 'last' => $row['last'] ?? null, 'command' => static::cronCommand()];
    }

    public static function cronCommand(): string
    {
        return '/usr/local/bin/ea-php'.PHP_MAJOR_VERSION.PHP_MINOR_VERSION.' '.base_path('artisan').' schedule:run >> /dev/null 2>&1';
    }

    public static function ago(?int $sec): string
    {
        if ($sec === null) {
            return '—';
        }
        if ($sec < 90) {
            return $sec.' '.__('soniya oldin');
        }
        if ($sec < 5400) {
            return round($sec / 60).' '.__('daqiqa oldin');
        }
        if ($sec < 172800) {
            return round($sec / 3600).' '.__('soat oldin');
        }

        return round($sec / 86400).' '.__('kun oldin');
    }
}
