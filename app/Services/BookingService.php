<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\OnlineBooking;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Onlayn qabul: bo'sh vaqtlar, bron, to'lovdan keyin avtomatik navbatga qo'yish.
 * Diqqat: bu kod autentifikatsiyasiz (ommaviy) ishlaydi, shuning uchun BARCHA so'rovlar
 * clinic_id bo'yicha qo'lda filtrlanadi (global scope ishlamaydi).
 */
class BookingService
{
    public static function defaults(): array
    {
        return [
            'online_enabled' => false, 'online_start' => '09:00', 'online_end' => '18:00',
            'online_days' => [1, 2, 3, 4, 5, 6], 'online_days_ahead' => 30, 'online_lead_min' => 60, 'online_hold_min' => 15,
            'payme_test' => false,
        ];
    }

    /** Klinikaning onlayn qabul oferta matni (klinika yozgan yoki standart). */
    public static function offerText(Clinic $c): string
    {
        $custom = trim((string) $c->setting('booking_offer'));
        if ($custom !== '') {
            return $custom;
        }
        $n = $c->name;

        return implode("\n\n", [
            __('1. Umumiy qoidalar')."\n".__('Ushbu ommaviy oferta :name klinikasi (keyingi o\'rinlarda — Klinika) tomonidan onlayn navbatga yozilish va to\'lov xizmatidan foydalanish shartlarini belgilaydi. Saytda "To\'lash va navbatga yozilish" tugmasini bosish oferta shartlarini to\'liq qabul qilishni anglatadi.', ['name' => $n]),
            __('2. Xizmat va to\'lov')."\n".__('Tanlangan xizmat narxi to\'lov sahifasida ko\'rsatiladi. To\'lov Payme tizimi orqali amalga oshiriladi. To\'lov muvaffaqiyatli o\'tgandan so\'ng navbat raqami beriladi va SMS yuboriladi.'),
            __('3. Bekor qilish va qaytarish')."\n".__('Qabuldan kamida 24 soat oldin bekor qilingan taqdirda to\'lov qaytariladi. Bemor qabulga kelmagan taqdirda to\'lov qaytarilmaydi. Klinika sababli qabul o\'tkazilmasa, to\'lov to\'liq qaytariladi.'),
            __('4. Shaxsiy ma\'lumotlar')."\n".__('Bemor kiritgan ma\'lumotlar (F.I.Sh., telefon, tug\'ilgan sana) faqat navbatga yozish, tibbiy xizmat ko\'rsatish va xabarnoma yuborish uchun qayta ishlanadi hamda uchinchi shaxslarga berilmaydi.'),
            __('5. Boshqa shartlar')."\n".__('Bemor to\'g\'ri ma\'lumot kiritishga majbur. Ko\'rsatilgan vaqtda kechikish qabul vaqtining qisqarishiga yoki ko\'chirilishiga olib kelishi mumkin.'),
        ]);
    }

    public static function cfg(Clinic $c, string $key)
    {
        return $c->setting($key, static::defaults()[$key] ?? null);
    }

    public static function clinicActive(Clinic $c): bool
    {
        return in_array($c->status, [Clinic::TRIAL, Clinic::ACTIVE], true);
    }

    public static function paymeKey(Clinic $c): ?string
    {
        $enc = $c->setting(static::cfg($c, 'payme_test') ? 'payme_test_key' : 'payme_key');
        if (! $enc) {
            return null;
        }
        try {
            return Crypt::decryptString($enc);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function paymeConfigured(Clinic $c): bool
    {
        return (bool) ($c->setting('payme_merchant_id') && static::paymeKey($c));
    }

    /** Test (sandbox) rejimi: faqat APP_DEBUG yoqilgan va Payme ulanmagan bo'lsa to'lovsiz tasdiqlash mumkin. */
    public static function sandbox(Clinic $c): bool
    {
        return (bool) config('app.debug') && ! static::paymeConfigured($c);
    }

    public static function isOpen(Clinic $c): bool
    {
        return $c->slug && static::cfg($c, 'online_enabled') && static::clinicActive($c)
            && (static::paymeConfigured($c) || static::sandbox($c));
    }

    public static function checkoutUrl(OnlineBooking $b, Clinic $c, string $returnUrl): string
    {
        $params = 'm='.$c->setting('payme_merchant_id').";ac.order_id={$b->id};a=".($b->amount * 100).';l='.($b->locale === 'ru' ? 'ru' : 'uz').';c='.$returnUrl;
        $base = static::cfg($c, 'payme_test') ? 'https://test.paycom.uz' : 'https://checkout.paycom.uz';

        return $base.'/'.base64_encode($params);
    }

    // ---------------- Ro'yxatlar ----------------

    public static function services(Clinic $c)
    {
        return Service::withoutGlobalScopes()->where('clinic_id', $c->id)->where('is_active', true)->where('online_enabled', true)->orderBy('name')->get();
    }

    public static function doctorsFor(Clinic $c, Service $s)
    {
        $linked = DB::table('doctor_service')->where('service_id', $s->id)->pluck('user_id');

        return User::withoutGlobalScopes()->where('clinic_id', $c->id)->where(fn ($q) => $q->where('role', User::DOCTOR)->orWhere('extra_roles', 'like', '%"doctor"%'))
            ->where('is_active', true)->where('online_enabled', true)
            ->where(function ($q) use ($linked) {
                // Xizmatga hech kim biriktirilmagan bo'lsa — barcha onlayn shifokorlar
                if ($linked->isEmpty()) {
                    return;
                }
                $q->whereIn('id', $linked);
            })->orderBy('name')->get();
    }

    // ---------------- Bo'sh vaqtlar ----------------

    public static function isFree(int $clinicId, int $doctorId, Carbon $start, Carbon $end, ?int $exceptBookingId = null): bool
    {
        $busyApp = Appointment::withoutGlobalScopes()->where('clinic_id', $clinicId)->where('doctor_id', $doctorId)
            ->whereNotIn('status', ['canceled', 'no_show'])->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists();
        if ($busyApp) {
            return false;
        }

        return ! OnlineBooking::withoutGlobalScopes()->where('clinic_id', $clinicId)->where('doctor_id', $doctorId)
            ->where('status', 'pending')->where('hold_expires_at', '>', now())
            ->when($exceptBookingId, fn ($q) => $q->where('id', '!=', $exceptBookingId))
            ->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists();
    }

    /** @return string[] 'H:i' */
    public static function slots(Clinic $c, Service $s, User $d, Carbon $date): array
    {
        $date = $date->copy()->startOfDay();
        if ($date->lt(today()) || $date->gt(today()->addDays((int) static::cfg($c, 'online_days_ahead')))) {
            return [];
        }
        if (! in_array((int) $date->dayOfWeekIso, array_map('intval', (array) static::cfg($c, 'online_days')), true)) {
            return [];
        }
        [$sh, $sm] = array_map('intval', explode(':', (string) static::cfg($c, 'online_start')) + [0, 0]);
        [$eh, $em] = array_map('intval', explode(':', (string) static::cfg($c, 'online_end')) + [0, 0]);
        $cursor = $date->copy()->setTime($sh, $sm);
        $limit = $date->copy()->setTime($eh, $em);
        $minStart = now()->addMinutes((int) static::cfg($c, 'online_lead_min'));
        $dur = max(10, (int) $s->duration_min);

        $out = [];
        while ($cursor->copy()->addMinutes($dur)->lte($limit)) {
            $end = $cursor->copy()->addMinutes($dur);
            if ($cursor->gte($minStart) && static::isFree($c->id, $d->id, $cursor, $end)) {
                $out[] = $cursor->format('H:i');
            }
            $cursor->addMinutes($dur);
        }

        return $out;
    }

    // ---------------- Bron yaratish ----------------

    /** @throws \RuntimeException('slot_taken') */
    public static function create(Clinic $c, Service $s, User $d, Carbon $start, array $p, ?string $ip, string $locale): OnlineBooking
    {
        $end = $start->copy()->addMinutes(max(10, (int) $s->duration_min));

        return DB::transaction(function () use ($c, $s, $d, $start, $end, $p, $ip, $locale) {
            if (! in_array($start->format('H:i'), static::slots($c, $s, $d, $start), true)) {
                throw new \RuntimeException('slot_taken');
            }

            return OnlineBooking::create([
                'clinic_id' => $c->id, 'token' => Str::random(32), 'service_id' => $s->id, 'doctor_id' => $d->id,
                'starts_at' => $start, 'ends_at' => $end, 'amount' => (int) $s->price,
                'last_name' => $p['last_name'], 'first_name' => $p['first_name'], 'middle_name' => $p['middle_name'] ?? null,
                'birth_date' => $p['birth_date'] ?? null, 'phone' => SmsService::normalize($p['phone']), 'pinfl' => ($p['pinfl'] ?? null) ?: null,
                'status' => 'pending', 'hold_expires_at' => now()->addMinutes((int) static::cfg($c, 'online_hold_min')),
                'offer_accepted_at' => now(), 'privacy_accepted_at' => now(), 'offer_hash' => sha1(static::offerText($c)), 'ip' => $ip, 'locale' => in_array($locale, ['uz', 'ru'], true) ? $locale : 'uz',
            ]);
        });
    }

    // ---------------- To'lovdan keyin: bemor + qabul + hisob + to'lov ----------------

    /** @throws \RuntimeException('slot_taken') */
    public static function confirm(OnlineBooking $b): OnlineBooking
    {
        $confirmed = false;
        DB::transaction(function () use (&$b, &$confirmed) {
            $b = OnlineBooking::withoutGlobalScopes()->lockForUpdate()->findOrFail($b->id);
            if ($b->status === 'confirmed') {
                return;
            }
            if (! static::isFree($b->clinic_id, $b->doctor_id, $b->starts_at, $b->ends_at, $b->id)) {
                throw new \RuntimeException('slot_taken');
            }
            $service = Service::withoutGlobalScopes()->find($b->service_id);

            $patient = static::findOrCreatePatient($b);
            $app = Appointment::create([
                'clinic_id' => $b->clinic_id, 'patient_id' => $patient->id, 'doctor_id' => $b->doctor_id, 'service_id' => $b->service_id,
                'starts_at' => $b->starts_at, 'ends_at' => $b->ends_at, 'status' => 'scheduled', 'source' => 'online',
                'comment' => 'Onlayn qabul (to\'langan)',
            ]);
            $inv = Invoice::create([
                'clinic_id' => $b->clinic_id, 'patient_id' => $patient->id, 'doctor_id' => $b->doctor_id,
                'number' => Invoice::nextNumber($b->clinic_id), 'total' => $b->amount, 'discount' => 0, 'paid' => $b->amount,
                'status' => 'paid', 'notes' => 'Onlayn qabul, Payme',
            ]);
            InvoiceItem::create(['clinic_id' => $b->clinic_id, 'invoice_id' => $inv->id, 'service_id' => $b->service_id, 'name' => $service?->name ?? 'Xizmat', 'qty' => 1, 'price' => $b->amount]);
            Payment::create(['clinic_id' => $b->clinic_id, 'invoice_id' => $inv->id, 'amount' => $b->amount, 'method' => 'payme', 'cashier_id' => null, 'paid_at' => now(), 'note' => 'Onlayn qabul']);

            $b->update(['status' => 'confirmed', 'patient_id' => $patient->id, 'appointment_id' => $app->id, 'invoice_id' => $inv->id, 'paid_at' => now()]);
            $confirmed = true;
        });
        if ($confirmed) {
            static::notify($b);
        }

        return $b;
    }

    protected static function findOrCreatePatient(OnlineBooking $b): Patient
    {
        $base = Patient::withoutGlobalScopes()->withTrashed()->where('clinic_id', $b->clinic_id);
        $hit = null;
        if ($b->pinfl) {
            $hit = (clone $base)->where('pinfl', $b->pinfl)->first();
        }
        if (! $hit) {
            $hit = (clone $base)->whereRaw('LOWER(TRIM(last_name)) = ?', [Patient::normalizeName($b->last_name)])
                ->whereRaw('LOWER(TRIM(first_name)) = ?', [Patient::normalizeName($b->first_name)])
                ->where('phone', $b->phone)->first();
        }
        if ($hit) {
            if ($hit->trashed()) {
                $hit->restore();
            }

            return $hit;
        }

        return Patient::create([
            'clinic_id' => $b->clinic_id, 'pinfl' => $b->pinfl, 'last_name' => $b->last_name, 'first_name' => $b->first_name,
            'middle_name' => $b->middle_name, 'birth_date' => $b->birth_date?->toDateString(), 'phone' => $b->phone, 'source' => 'Onlayn qabul',
        ]);
    }

    public static function queueNumber(Appointment $a): int
    {
        return Appointment::withoutGlobalScopes()->where('clinic_id', $a->clinic_id)->where('doctor_id', $a->doctor_id)
            ->whereDate('starts_at', $a->starts_at->toDateString())->whereNotIn('status', ['canceled', 'no_show'])
            ->where('starts_at', '<=', $a->starts_at)->count();
    }

    public static function notify(OnlineBooking $b): void
    {
        $c = Clinic::find($b->clinic_id);
        $a = Appointment::withoutGlobalScopes()->find($b->appointment_id);
        $doc = User::withoutGlobalScopes()->find($b->doctor_id);
        if (! $c || ! $a) {
            return;
        }
        SmsService::event($c, 'booking_confirmed', $b->phone, [
            'name' => $b->first_name, 'date' => $b->starts_at->format('d.m.Y H:i'), 'doctor' => $doc?->name ?? '', 'n' => static::queueNumber($a),
        ]);
        // Ilova ichida xabar: qabulxona va admin/direktorlarga
        \App\Services\Notify::send(\App\Services\Notify::roles($c->id, ['receptionist']), __('Yangi onlayn qabul'), $b->full_name.' · '.$b->starts_at->format('d.m H:i').' · '.($doc?->name ?? ''), 'success', route('app.calendar'), 'booking', $c->id);
    }

    // ---------------- Qaytarish / muddati o'tish ----------------

    /** Payme to'lovni bekor qilganda: qabul hali boshlanmagan bo'lsa qaytarishga ruxsat. */
    public static function refund(OnlineBooking $b): void
    {
        $app = Appointment::withoutGlobalScopes()->find($b->appointment_id);
        if ($app && $app->status !== 'scheduled') {
            throw PaymeException::make(-31007, 'Xizmat ko\'rsatilgan, bekor qilib bo\'lmaydi', 'Услуга оказана, отмена невозможна', 'Service rendered, cannot cancel');
        }
        $app?->update(['status' => 'canceled']);
        if ($b->invoice_id) {
            Payment::withoutGlobalScopes()->where('invoice_id', $b->invoice_id)->delete();
            Invoice::withoutGlobalScopes()->whereKey($b->invoice_id)->update(['paid' => 0, 'status' => 'canceled']);
        }
        $b->update(['status' => 'refunded']);
    }

    public static function expireStale(): int
    {
        return OnlineBooking::withoutGlobalScopes()->where('status', 'pending')->where('hold_expires_at', '<', now())->update(['status' => 'expired']);
    }
}
