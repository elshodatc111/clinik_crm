<?php

namespace App\Services;

use App\Models\Clinic;
use App\Models\OnlineBooking;
use App\Models\PaymeTransaction;

/**
 * Har bir klinikaning O'Z Payme kassasi uchun Merchant API (onlayn qabul to'lovlari).
 * Pul to'g'ridan-to'g'ri klinikaning Payme hisobiga tushadi; platforma aralashmaydi.
 * Account maydoni: order_id (OnlineBooking).
 */
class ClinicPaymeService extends PaymeService
{
    public function __construct(protected Clinic $clinic)
    {
    }

    protected function secret(): ?string
    {
        return BookingService::paymeKey($this->clinic);
    }

    protected function type(): string
    {
        return 'booking';
    }

    protected function findOrder(int $id): ?object
    {
        return OnlineBooking::withoutGlobalScopes()->where('clinic_id', $this->clinic->id)->find($id);
    }

    protected function payable(object $order): bool
    {
        return $order->status === 'pending' && BookingService::clinicActive($this->clinic);
    }

    protected function amountSom(object $order): int
    {
        return (int) $order->amount;
    }

    protected function fulfill(PaymeTransaction $tx, object $order): void
    {
        try {
            BookingService::confirm($order);
        } catch (\RuntimeException $e) {
            // Vaqt boshqa bemorga band bo'lib qolgan: Payme to'lovni bekor qiladi
            throw $this->cannotPerform();
        }
    }

    protected function afterFulfill(PaymeTransaction $tx, object $order): void
    {
    }

    protected function refund(PaymeTransaction $tx, object $order, $reason): void
    {
        BookingService::refund($order);
    }

    protected function onCreated(PaymeTransaction $tx, object $order): void
    {
        // To'lov jarayonida vaqt bo'shab ketmasin
        $until = now()->addMinutes(30);
        if (! $order->hold_expires_at || $order->hold_expires_at->lt($until)) {
            $order->update(['hold_expires_at' => $until]);
        }
    }

    protected function onCanceledUnpaid(PaymeTransaction $tx): void
    {
        OnlineBooking::withoutGlobalScopes()->whereKey($tx->topup_order_id)->where('status', 'pending')->update(['status' => 'expired']);
    }

    protected function statementQuery()
    {
        return PaymeTransaction::where('order_type', 'booking')->where('clinic_id', $this->clinic->id);
    }
}
