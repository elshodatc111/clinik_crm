<?php

namespace App\Console\Commands;

use App\Models\Clinic;
use App\Services\ClinicDiagnostics;
use Illuminate\Console\Command;

class DiagnoseClinics extends Command
{
    protected $signature = 'clinics:diagnose';

    protected $description = "Klinikalar kamchiliklarini aniqlab, admin/direktorlarga ilova ichida xabar yuboradi";

    public function handle(): int
    {
        $n = 0;
        Clinic::whereIn('status', [Clinic::TRIAL, Clinic::ACTIVE, Clinic::VIEW_ONLY, Clinic::BLOCKED])->each(function (Clinic $c) use (&$n) {
            $n += ClinicDiagnostics::notify($c);
        });
        $this->info("Yuborildi: {$n}");

        return self::SUCCESS;
    }
}
