<?php

namespace App\Services;

use App\Models\BalanceTransaction;
use App\Models\Clinic;
use App\Models\ClinicStatusLog;
use App\Models\Patient;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use App\Support\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BillingService
{
    public const PERIOD_DAYS = 30;
    public const VIEW_ONLY_DAYS = 60;   // paid_until dan 60 kun o'tgach (3-oy oxiri) bloklanadi
    public const PURGE_AFTER_BLOCK_DAYS = 30;

    public const TENANT_TABLES = [
        'lab_orders', 'payments', 'invoice_items', 'invoices', 'prescriptions', 'visits',
        'appointments', 'services', 'patients', 'balance_transactions', 'topup_orders', 'clinic_status_logs',
    ];

    public static function appName(): string
    {
        return Setting::get('app_name', 'Klinika CRM');
    }

    public static function setupFee(): int
    {
        return (int) Setting::get('setup_fee', 1000000);
    }

    public static function daysUntil(Carbon $to): int
    {
        return (int) ceil(($to->getTimestamp() - now()->getTimestamp()) / 86400);
    }

    public static function planFor(int $count): ?Plan
    {
        $n = max($count, 1);

        return Plan::where('is_active', true)
            ->where('min_patients', '<=', $n)
            ->where(fn ($q) => $q->whereNull('max_patients')->orWhere('max_patients', '>=', $n))
            ->orderByDesc('min_patients')
            ->first();
    }

    public static function monthlyPrice(Clinic $c): int
    {
        return (int) (static::planFor($c->patientCount())?->monthly_price ?? 0);
    }

    public static function changeStatus(Clinic $c, string $to, ?string $reason = null): void
    {
        if ($c->status === $to) {
            return;
        }
        ClinicStatusLog::create(['clinic_id' => $c->id, 'from_status' => $c->status, 'to_status' => $to, 'reason' => $reason]);
        $c->status = $to;
        $c->blocked_at = $to === Clinic::BLOCKED ? now() : null;
        $c->save();
    }

    /** Balansni o'zgartirish (musbat — kirim, manfiy — chiqim). */
    public static function adjust(Clinic $clinic, int $amount, string $type, ?string $desc = null, ?int $userId = null): Clinic
    {
        return DB::transaction(function () use ($clinic, $amount, $type, $desc, $userId) {
            $c = Clinic::whereKey($clinic->id)->lockForUpdate()->firstOrFail();
            $c->balance = (int) $c->balance + $amount;
            $c->save();
            BalanceTransaction::create([
                'clinic_id' => $c->id, 'type' => $type, 'amount' => $amount,
                'balance_after' => $c->balance, 'description' => $desc, 'user_id' => $userId,
            ]);

            return $c;
        });
    }

    /** Demo → pullik obuna. Xato bo'lsa matn qaytaradi. */
    public static function subscribe(Clinic $c): ?string
    {
        $c->refresh();
        if ($c->status !== Clinic::TRIAL) {
            return __("Obunaga faqat demo rejimidan o'tish mumkin.");
        }
        $fee = static::setupFee();
        if ($c->balance < $fee) {
            return __("Balansda mablag' yetarli emas. Ulanish to'lovi: :sum", ['sum' => Tenant::money($fee)]);
        }
        DB::transaction(function () use ($c, $fee) {
            $c = static::adjust($c, -$fee, 'setup_fee', "Ulanish to'lovi");
            $c->setup_paid_at = now();
            $c->paid_until = now()->addDays(static::PERIOD_DAYS); // 1-oy bepul
            $c->plan_id = static::planFor($c->patientCount())?->id;
            $c->last_notice_key = null;
            $c->save();
            static::changeStatus($c, Clinic::ACTIVE, "Ulanish to'lovi to'landi");
        });
        SmsService::send($c->phone, static::appName().": obuna faollashtirildi. Birinchi oy bepul. Rahmat!", $c->id);

        return null;
    }

    /** Oylik to'lovni balansdan yechib, muddatni uzaytirish. */
    public static function renew(Clinic $c): bool
    {
        $c->refresh();
        $count = $c->patientCount();
        $plan = static::planFor($count);
        $price = (int) ($plan?->monthly_price ?? 0);
        if ($c->balance < $price) {
            return false;
        }
        DB::transaction(function () use ($c, $price, $plan) {
            $c = static::adjust($c, -$price, 'charge', 'Oylik obuna'.($plan ? " ({$plan->name})" : ''));
            $base = ($c->paid_until && $c->paid_until->isFuture()) ? $c->paid_until : now();
            $c->paid_until = $base->copy()->addDays(static::PERIOD_DAYS);
            $c->plan_id = $plan?->id;
            $c->last_notice_key = null;
            $c->save();
            static::changeStatus($c, Clinic::ACTIVE, 'Obuna uzaytirildi');
        });

        return true;
    }

    /** Balans to'ldirilgach: muddati o'tgan bo'lsa darhol uzaytirishga urinadi. */
    public static function afterTopup(Clinic $c): void
    {
        $c->refresh();
        $due = in_array($c->status, [Clinic::VIEW_ONLY, Clinic::BLOCKED], true)
            || ($c->status === Clinic::ACTIVE && $c->paid_until && $c->paid_until->lessThanOrEqualTo(now()));
        if ($due && static::renew($c)) {
            SmsService::send($c->phone, static::appName().': obuna uzaytirildi. Rahmat!', $c->id);
        }
    }

    protected static function notice(Clinic $c, string $key, string $text): void
    {
        if ($c->last_notice_key === $key) {
            return;
        }
        SmsService::send($c->phone, $text, $c->id);
        $c->last_notice_key = $key;
        $c->saveQuietly();
    }

    public static function runDaily(): void
    {
        $grace = (int) Setting::get('demo_grace_days', 0);
        foreach (Clinic::query()->get() as $c) {
            try {
                static::process($c, $grace);
            } catch (\Throwable $e) {
                report($e);
            }
        }
        static::cleanupBackups();
    }

    protected static function process(Clinic $c, int $grace): void
    {
        $app = static::appName();
        $now = now();

        switch ($c->status) {
            case Clinic::TRIAL:
                if ($c->trial_ends_at && $now->greaterThan($c->trial_ends_at->copy()->addDays($grace))) {
                    SmsService::send($c->phone, "{$app}: demo muddati tugadi va obuna olinmagani uchun barcha ma'lumotlaringiz o'chirildi.", $c->id);
                    static::purge($c, 'demo muddati tugadi', false);

                    return;
                }
                $days = $c->trialDaysLeft();
                if (in_array($days, [7, 3, 1], true)) {
                    static::notice($c, "trial{$days}", "{$app}: demo versiya tugashiga {$days} kun qoldi. Obunaga o'tmasangiz ma'lumotlar o'chiriladi.");
                }
                break;

            case Clinic::ACTIVE:
                if ($c->paid_until && $c->paid_until->lessThanOrEqualTo($now)) {
                    if (! static::renew($c)) {
                        static::changeStatus($c, Clinic::VIEW_ONLY, "Oylik to'lov to'lanmadi");
                        static::notice($c, 'viewonly', "{$app}: oylik to'lov to'lanmadi. Tizim faqat ko'rish rejimiga o'tdi. Balansni to'ldiring.");
                    }
                } elseif ($c->paid_until) {
                    $left = static::daysUntil($c->paid_until);
                    if ($left <= 3 && $left >= 0 && $c->balance < static::monthlyPrice($c)) {
                        static::notice($c, "low{$left}", "{$app}: obuna tugashiga {$left} kun qoldi, balansda mablag' yetarli emas. Balansni to'ldiring.");
                    }
                }
                break;

            case Clinic::VIEW_ONLY:
                if (static::renew($c)) {
                    break;
                }
                if ($c->paid_until && $now->greaterThanOrEqualTo($c->paid_until->copy()->addDays(static::VIEW_ONLY_DAYS))) {
                    static::changeStatus($c, Clinic::BLOCKED, "3-oy yakunigacha to'lanmadi");
                    static::notice($c, 'blocked', "{$app}: klinikangiz bloklandi. 30 kundan keyin barcha ma'lumotlaringiz o'chiriladi. To'lovni amalga oshiring.");
                }
                break;

            case Clinic::BLOCKED:
                if (static::renew($c)) {
                    break;
                }
                $purgeAt = $c->blocked_at?->copy()->addDays(static::PURGE_AFTER_BLOCK_DAYS);
                if ($purgeAt) {
                    $left = static::daysUntil($purgeAt);
                    if (in_array($left, [14, 7, 1], true)) {
                        static::notice($c, "purge{$left}", "{$app}: {$left} kundan keyin barcha ma'lumotlaringiz o'chiriladi. To'lovni amalga oshiring.");
                    }
                    if ($now->greaterThanOrEqualTo($purgeAt) && ! $c->hold_deletion) {
                        SmsService::send($c->phone, "{$app}: to'lov qilinmagani uchun shartnoma bekor qilindi va ma'lumotlaringiz o'chirildi.", $c->id);
                        static::purge($c, "to'lanmagan, 30 kun blok", true);
                    }
                }
                break;
        }
    }

    /** Klinikani va uning barcha ma'lumotlarini o'chirish (ixtiyoriy: avval shifrlangan zaxira). */
    public static function purge(Clinic $c, string $reason, bool $backup = true): void
    {
        Log::warning("Klinika o'chirilmoqda #{$c->id} {$c->name}: {$reason}");
        if ($backup) {
            static::backup($c);
        }
        DB::transaction(function () use ($c) {
            User::where('clinic_id', $c->id)->delete();
            $c->delete(); // klinika_id FK cascade orqali barcha jadvallar tozalanadi
        });
        Storage::disk('public')->deleteDirectory('clinics/'.$c->id);
        Storage::disk('local')->deleteDirectory('clinics/'.$c->id);
    }

    /** Shifrlangan zaxira (storage/app/private/backups). 30 kundan keyin avtomatik o'chadi. */
    public static function backup(Clinic $c): void
    {
        $path = 'backups/clinic_'.$c->id.'_'.now()->format('Ymd_His').'.enc';
        Storage::disk('local')->put($path, '');
        $full = Storage::disk('local')->path($path);
        $write = function (string $table, $rows) use ($full) {
            file_put_contents($full, \Illuminate\Support\Facades\Crypt::encryptString(json_encode(['t' => $table, 'rows' => $rows], JSON_UNESCAPED_UNICODE))."\n", FILE_APPEND);
        };
        $write('clinics', [$c->getAttributes()]);
        $write('users', DB::table('users')->where('clinic_id', $c->id)->get()->all());
        foreach (array_reverse(self::TENANT_TABLES) as $t) {
            DB::table($t)->where('clinic_id', $c->id)->orderBy('id')->chunk(500, fn ($rows) => $write($t, $rows->all()));
        }
    }

    protected static function cleanupBackups(): void
    {
        $disk = Storage::disk('local');
        foreach ($disk->files('backups') as $f) {
            if ($disk->lastModified($f) < now()->subDays(30)->getTimestamp()) {
                $disk->delete($f);
            }
        }
    }
}
