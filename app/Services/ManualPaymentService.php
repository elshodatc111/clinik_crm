<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Clinic;
use App\Models\ManualPayment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Platforma obunasi uchun kartaga qo'lda to'lov: yuborish, tasdiqlash, bekor qilish. */
class ManualPaymentService
{
    public static function enabled(): bool
    {
        return Setting::get('card_enabled', '1') !== '0' && static::cardNumber() !== '';
    }

    public static function cardNumber(): string
    {
        return preg_replace('/\D/', '', (string) Setting::get('card_number', ''));
    }

    public static function cardFormatted(?string $n = null): string
    {
        return trim(chunk_split($n ?? static::cardNumber(), 4, ' '));
    }

    public static function card(): array
    {
        return [
            'number' => static::cardNumber(), 'formatted' => static::cardFormatted(),
            'holder' => (string) Setting::get('card_holder', ''), 'bank' => (string) Setting::get('card_bank', ''),
            'note' => (string) Setting::get('card_note', ''),
        ];
    }

    public static function pendingCount(): int
    {
        return ManualPayment::where('status', 'pending')->count();
    }

    /** @return array{0:?ManualPayment,1:?string} [to'lov, xato matni] */
    public static function submit(Clinic $c, User $u, array $d, UploadedFile $file): array
    {
        if (! static::enabled()) {
            return [null, __("Kartaga to'lov hozircha o'chirilgan.")];
        }
        if (ManualPayment::where('clinic_id', $c->id)->where('status', 'pending')->count() >= 3) {
            return [null, __("Tekshirilmayotgan 3 ta to'lovingiz bor. Avval ularning tasdiqlanishini kuting.")];
        }
        $hash = sha1_file($file->getRealPath());
        if (ManualPayment::where('receipt_hash', $hash)->whereIn('status', ['pending', 'approved'])->exists()) {
            return [null, __("Bu chek avval yuborilgan. Boshqa to'lov uchun yangi chek yuklang.")];
        }
        $path = $file->storeAs('clinics/'.$c->id.'/receipts', now()->format('Ymd_His').'_'.substr($hash, 0, 8).'.'.($file->guessExtension() ?: 'jpg'), 'local');

        $p = ManualPayment::create([
            'clinic_id' => $c->id, 'user_id' => $u->id, 'amount' => (int) $d['amount'], 'paid_at' => $d['paid_at'],
            'payer_card' => $d['payer_card'] ?? null, 'note' => $d['note'] ?? null, 'card_number' => static::cardNumber(),
            'receipt_path' => $path, 'receipt_hash' => $hash, 'status' => 'pending',
        ]);
        Notify::send(Notify::superAdmins(), __("Yangi to'lov cheki"), $c->name.' — '.number_format((int) $p->amount, 0, '.', ' ').' '.__("so'm"), 'warning', route('admin.payments.index'), 'payment');
        AuditLog::record('pay_submit', $u->id, $c->id, ['payment' => $p->id, 'amount' => $p->amount]);

        return [$p, null];
    }

    public static function cancelByClinic(ManualPayment $p, User $u): bool
    {
        return DB::transaction(function () use ($p, $u) {
            $p = ManualPayment::lockForUpdate()->find($p->id);
            if (! $p || $p->status !== 'pending') {
                return false;
            }
            $p->update(['status' => 'canceled', 'reviewed_at' => now()]);
            AuditLog::record('pay_cancel', $u->id, $p->clinic_id, ['payment' => $p->id]);

            return true;
        });
    }

    /** Tasdiqlash: balans to'ldiriladi. Ikki marta tasdiqlashdan himoyalangan. */
    public static function approve(ManualPayment $p, User $admin, ?int $amount = null): ?string
    {
        $approved = null;
        $clinic = null;
        $err = DB::transaction(function () use ($p, $admin, $amount, &$approved, &$clinic) {
            $p = ManualPayment::lockForUpdate()->find($p->id);
            if (! $p || $p->status !== 'pending') {
                return __("Bu to'lov allaqachon ko'rib chiqilgan.");
            }
            $clinic = Clinic::find($p->clinic_id);
            if (! $clinic) {
                return __('Klinika topilmadi.');
            }
            $approved = (int) ($amount ?: $p->amount);
            $c = BillingService::adjust($clinic, $approved, 'topup', "Karta to'lovi (#{$p->id})", $admin->id);
            $tx = \App\Models\BalanceTransaction::where('clinic_id', $c->id)->latest('id')->first();
            $p->update(['status' => 'approved', 'approved_amount' => $approved, 'reviewed_by' => $admin->id, 'reviewed_at' => now(), 'reject_reason' => null, 'balance_tx_id' => $tx?->id]);
            AuditLog::record('pay_approve', $admin->id, $c->id, ['payment' => $p->id, 'amount' => $approved, 'claimed' => $p->amount]);

            return null;
        });
        if ($err) {
            return $err;
        }
        $p->refresh();
        BillingService::afterTopup($clinic);
        $sum = number_format($approved, 0, '.', ' ');
        PlatformSms::send('topup_confirmed', $clinic->phone, ['amount' => $sum], $clinic->id);
        Notify::toAdmins($clinic->id, __("To'lovingiz tasdiqlandi"), __("Balansga :sum so'm qo'shildi.", ['sum' => $sum]), 'success', route('app.billing'), 'payment');

        return null;
    }

    public static function reject(ManualPayment $p, User $admin, string $reason): ?string
    {
        $clinic = null;
        $err = DB::transaction(function () use ($p, $admin, $reason, &$clinic) {
            $p = ManualPayment::lockForUpdate()->find($p->id);
            if (! $p || $p->status !== 'pending') {
                return __("Bu to'lov allaqachon ko'rib chiqilgan.");
            }
            $clinic = Clinic::find($p->clinic_id);
            $p->update(['status' => 'rejected', 'reject_reason' => mb_substr(trim($reason), 0, 500), 'reviewed_by' => $admin->id, 'reviewed_at' => now()]);
            AuditLog::record('pay_reject', $admin->id, $p->clinic_id, ['payment' => $p->id, 'reason' => mb_substr(trim($reason), 0, 120)]);

            return null;
        });
        if ($err) {
            return $err;
        }
        if ($clinic) {
            PlatformSms::send('topup_rejected', $clinic->phone, ['reason' => mb_substr(trim($reason), 0, 80)], $clinic->id);
            Notify::toAdmins($clinic->id, __("To'lovingiz bekor qilindi"), __('Sabab: :r', ['r' => trim($reason)]), 'danger', route('app.billing'), 'payment');
        }

        return null;
    }

    public static function receiptResponse(ManualPayment $p)
    {
        abort_unless(Storage::disk('local')->exists($p->receipt_path), 404);

        return Storage::disk('local')->response($p->receipt_path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
