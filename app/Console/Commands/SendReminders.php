<?php

namespace App\Console\Commands;

use App\Http\Controllers\App\SmsSettingsController;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Services\SmsService;
use Illuminate\Console\Command;

class SendReminders extends Command
{
    protected $signature = 'reminders:send';

    protected $description = 'Bemorlarga qabul eslatma SMS lari (klinikaning o\'z Eskiz akkaunti orqali)';

    public function handle(): int
    {
        $sent = 0;
        Clinic::whereIn('status', [Clinic::TRIAL, Clinic::ACTIVE])->each(function (Clinic $c) use (&$sent) {
            if (! SmsService::clinicCreds($c->id)) {
                return;
            }
            $on24 = (bool) $c->setting('sms_remind_24h', true);
            $on2 = (bool) $c->setting('sms_remind_2h', true);
            if (! $on24 && ! $on2) {
                return;
            }
            $tpl = $c->setting('sms_tpl_reminder') ?: SmsSettingsController::DEFAULT_TPL;

            $apps = Appointment::withoutGlobalScopes()->with(['patient', 'doctor'])
                ->where('clinic_id', $c->id)->whereIn('status', ['scheduled', 'arrived'])
                ->where('starts_at', '>', now())->where('starts_at', '<=', now()->addHours(24))->get();

            foreach ($apps as $a) {
                $hoursLeft = now()->diffInMinutes($a->starts_at, false) / 60;
                $which = null;
                if ($on2 && $hoursLeft <= 2 && ! $a->remind_2h_at) {
                    $which = 'remind_2h_at';
                } elseif ($on24 && $hoursLeft > 3 && ! $a->remind_24h_at) {
                    $which = 'remind_24h_at';
                }
                if (! $which || ! $a->patient?->phone) {
                    continue;
                }
                $text = strtr($tpl, [
                    ':name' => $a->patient->first_name, ':date' => $a->starts_at->format('d.m.Y H:i'), ':clinic' => $c->name,
                    ':doctor' => $a->doctor?->name ?? '', ':address' => $c->address ?? '',
                ]);
                SmsService::send($a->patient->phone, $text, $c->id, true);
                $upd = [$which => now()];
                if ($which === 'remind_2h_at') {
                    $upd['remind_24h_at'] = $a->remind_24h_at ?? now();
                }
                Appointment::withoutGlobalScopes()->whereKey($a->id)->update($upd);
                $sent++;
            }
        });
        $this->info("Eslatmalar yuborildi: {$sent}");

        return self::SUCCESS;
    }
}
