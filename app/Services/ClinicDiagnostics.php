<?php

namespace App\Services;

use App\Models\Clinic;
use Illuminate\Support\Facades\DB;

/** Klinika sozlamalari va ishlashidagi kamchiliklarni aniqlaydi (superadmin va klinika admini uchun). */
class ClinicDiagnostics
{
    /** @return array<int,array{code:string,level:string,title:string,body:string,route:?string}> */
    public static function run(Clinic $c): array
    {
        $id = $c->id;
        $out = [];
        $add = function (string $code, string $level, string $title, string $body, ?string $route = null) use (&$out) {
            $out[] = compact('code', 'level', 'title', 'body', 'route');
        };
        $t = fn ($table) => DB::table($table)->where('clinic_id', $id);

        // --- Obuna
        if ($c->status === Clinic::BLOCKED || $c->status === Clinic::VIEW_ONLY) {
            $add('billing_blocked', 'danger', "Obuna to'lanmagan", "Tizim cheklangan rejimda. Balansni to'ldiring, aks holda ma'lumotlar o'chiriladi.", 'app.billing');
        } elseif ($c->status === Clinic::TRIAL && $c->trialDaysLeft() <= 3) {
            $add('trial_ending', 'warning', 'Demo muddati tugamoqda', ":n kun qoldi. Obunaga o'ting, aks holda ma'lumotlar o'chiriladi.", 'app.billing');
        } elseif ($c->status === Clinic::ACTIVE && $c->paid_until && $c->paid_until->diffInDays(now(), false) > -3) {
            $fee = BillingService::monthlyPrice($c);
            if ($c->balance < $fee) {
                $add('low_balance', 'warning', 'Balans yetarli emas', "Obuna to'lovi uchun balansni to'ldiring (kerak: :fee so'm).", 'app.billing');
            }
        }

        // --- Asosiy sozlamalar
        if (! $t('services')->where('is_active', true)->exists()) {
            $add('no_services', 'warning', "Xizmatlar kiritilmagan", "Narxlar ro'yxati bo'sh. Hisob-faktura va onlayn qabul uchun xizmatlarni kiriting.", 'app.services.index');
        }
        $hasDoctor = DB::table('users')->where('clinic_id', $id)->where('is_active', true)->where(fn ($q) => $q->where('role', 'doctor')->orWhere('extra_roles', 'like', '%"doctor"%'))->exists();
        if (! $hasDoctor) {
            $add('no_doctor', 'warning', "Shifokor qo'shilmagan", "Qabul va tashriflar uchun kamida bitta shifokor xodim qo'shing.", 'app.staff.index');
        }
        if (! $c->phone || ! $c->address) {
            $add('profile_incomplete', 'info', "Klinika ma'lumotlari to'liq emas", "Telefon va manzilni kiriting — ular hujjat va SMS larda chiqadi.", 'app.profile.edit');
        }

        // --- SMS
        $creds = SmsService::clinicCreds($id);
        if (! $creds) {
            $add('sms_not_connected', 'info', 'SMS ulanmagan', "Eskiz akkauntini ulab, bemorlarga eslatma SMS yuborishni yoqing.", 'app.sms.settings');
        } elseif ($c->setting('sms_enabled', true) === false) {
            $add('sms_disabled', 'info', "SMS xizmati o'chirilgan", "Bemorlarga SMS yuborilmayapti. Kerak bo'lsa SMS sozlamalarida yoqing.", 'app.sms.settings');
        } else {
            $approved = collect(array_keys(\App\Support\SmsEvents::all()))->filter(fn ($e) => \App\Support\SmsEvents::get($c, $e)['status'] === 'approved')->count();
            if ($approved === 0 && $c->setting('sms_need_approval', true)) {
                $add('sms_no_templates', 'warning', 'SMS shablonlari tasdiqlanmagan', "Eskiz shablonlarni tasdiqlamaguncha SMS yuborilmaydi. SMS sozlamalarida shablonlarni Eskizga yuboring.", 'app.sms.settings');
            }
            $failed = $t('sms_logs')->where('status', 'failed')->where('created_at', '>=', now()->subDay())->count();
            if ($failed >= 3) {
                $add('sms_failing', 'danger', 'SMS yuborishda xatolar', "So'nggi 24 soatda :n ta SMS yuborilmadi. Eskiz balansi yoki akkauntni tekshiring.", 'app.sms.index');
            }
        }

        // --- Onlayn qabul
        if ($c->setting('online_enabled') && ! BookingService::paymeConfigured($c)) {
            $add('online_no_payme', 'warning', 'Onlayn qabul uchun Payme ulanmagan', "Onlayn qabul yoqilgan, lekin klinikaning Payme kassasi sozlanmagan — bemorlar to'lay olmaydi.", 'app.online.edit');
        }

        // --- Ish jarayoni
        $unclosed = $t('payments')->whereDate('paid_at', '>=', now()->subDays(7))->whereDate('paid_at', '<', today())->selectRaw('DATE(paid_at) d')->groupBy('d')->pluck('d')
            ->filter(fn ($d) => ! $t('cash_closings')->whereDate('date', $d)->exists())->count();
        if ($unclosed > 0) {
            $add('cash_unclosed', 'warning', 'Kassa kunlari yopilmagan', "So'nggi 7 kunda :n ta kunning kassasi yopilmagan.", 'app.cash.index');
        }
        $debts = $t('invoices')->whereIn('status', ['unpaid', 'partial'])->where('created_at', '<', now()->subDays(14))->count();
        if ($debts > 0) {
            $add('old_debts', 'info', "Eski qarzdorliklar bor", ":n ta hisob-faktura 14 kundan beri to'lanmagan.", 'app.invoices.index');
        }
        $labs = $t('lab_orders')->where('status', '!=', 'done')->where('created_at', '<', now()->subDays(3))->count();
        if ($labs > 0) {
            $add('old_labs', 'info', "Natijasi kutilayotgan tahlillar", ":n ta tahlil 3 kundan beri natijasiz.", 'app.lab.index');
        }
        $lastAppt = $t('appointments')->max('created_at');
        if ($c->status === Clinic::ACTIVE && $c->created_at->lt(now()->subDays(7)) && (! $lastAppt || now()->diffInDays($lastAppt) >= 7)) {
            $add('inactive', 'info', 'Faollik past', "So'nggi 7 kunda yangi qabul yaratilmadi. Yordam kerak bo'lsa biz bilan bog'laning.", null);
        }

        // :n, :fee o'rinlarini to'ldirish uchun ma'lumotlar
        $repl = ['trial_ending' => ['n' => $c->trialDaysLeft()], 'low_balance' => ['fee' => number_format(BillingService::monthlyPrice($c), 0, '.', ' ')],
            'sms_failing' => ['n' => $failed ?? 0], 'cash_unclosed' => ['n' => $unclosed], 'old_debts' => ['n' => $debts], 'old_labs' => ['n' => $labs]];
        foreach ($out as &$i) {
            $i['title'] = __($i['title']);
            $i['body'] = __($i['body'], $repl[$i['code']] ?? []);
        }

        return $out;
    }

    /** Topilgan kamchiliklarni admin/direktorlarga ilova ichida yuborish. */
    public static function notify(Clinic $c, ?array $issues = null, ?int $senderId = null): int
    {
        $issues ??= static::run($c);
        $n = 0;
        foreach ($issues as $i) {
            $n += Notify::send(Notify::admins($c->id), $i['title'], $i['body'], $i['level'] === 'info' ? 'info' : $i['level'], $i['route'] ? route($i['route']) : null, 'diagnostic', $c->id, 'diag:'.$i['code'], null, $senderId);
        }

        return $n;
    }
}
