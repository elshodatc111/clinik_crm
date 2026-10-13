<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/** Ma'lumotlar bazasining siqilgan nusxasi (mysqldump shart emas). Saqlash muddati: 14 kun. */
class DbBackup extends Command
{
    protected $signature = 'db:backup {--keep=14 : Necha kunlik nusxa saqlansin}';

    protected $description = "Ma'lumotlar bazasidan zaxira nusxa olish (storage/app/backups)";

    public function handle(): int
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);
        $name = 'db-'.now()->format('Ymd-His');

        try {
            if (DB::getDriverName() === 'sqlite') {
                $src = DB::connection()->getDatabaseName();
                if (! is_file($src)) {
                    $this->warn('SQLite fayl topilmadi (xotira bazasi).');

                    return self::SUCCESS;
                }
                $file = "$dir/$name.sqlite.gz";
                file_put_contents($file, gzencode((string) file_get_contents($src), 6));
            } else {
                $file = "$dir/$name.sql.gz";
                $gz = gzopen($file, 'wb6');
                $this->dump($gz);
                gzclose($gz);
            }
        } catch (\Throwable $e) {
            $this->error('Zaxira xatosi: '.$e->getMessage());
            \App\Services\SystemMonitor::exception($e);

            return self::FAILURE;
        }
        @chmod($file, 0600);

        foreach (File::files($dir) as $f) {
            if (str_starts_with($f->getFilename(), 'db-') && $f->getMTime() < now()->subDays((int) $this->option('keep'))->getTimestamp()) {
                @unlink($f->getPathname());
            }
        }
        $this->info('Tayyor: '.$file.' ('.round(filesize($file) / 1024).' KB)');

        return self::SUCCESS;
    }

    protected function dump($gz): void
    {
        $pdo = DB::connection()->getPdo();
        gzwrite($gz, "-- Zaxira ".now()->toDateTimeString()."\nSET FOREIGN_KEY_CHECKS=0;\nSET NAMES utf8mb4;\n\n");
        foreach (DB::select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"') as $row) {
            $table = array_values((array) $row)[0];
            $create = (array) DB::selectOne("SHOW CREATE TABLE `$table`");
            gzwrite($gz, "DROP TABLE IF EXISTS `$table`;\n".array_values($create)[1].";\n\n");
            $batch = [];
            $flush = function () use (&$batch, $gz, $table) {
                if ($batch) {
                    gzwrite($gz, "INSERT INTO `$table` VALUES ".implode(',', $batch).";\n");
                    $batch = [];
                }
            };
            foreach (DB::table($table)->cursor() as $r) {
                $vals = array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), array_values((array) $r));
                $batch[] = '('.implode(',', $vals).')';
                if (count($batch) >= 200) {
                    $flush();
                }
            }
            $flush();
            gzwrite($gz, "\n");
        }
        gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n");
    }
}
