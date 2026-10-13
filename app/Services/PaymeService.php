<?php

namespace App\Services;

use App\Models\Clinic;
use App\Models\PaymeTransaction;
use App\Models\Setting;
use App\Models\TopupOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Payme Merchant API (JSON-RPC 2.0). Faqat Merchant orqali to'lov qabul qilinadi.
 * Account maydoni: order_id (TopupOrder).  Summalar tiyinda keladi (1 so'm = 100 tiyin).
 */
class PaymeService
{
    public const TIMEOUT_MS = 43200000; // 12 soat

    public static function checkoutUrl(TopupOrder $order, string $returnUrl): string
    {
        $merchant = Setting::get('payme_merchant_id');
        $params = "m={$merchant};ac.order_id={$order->id};a=".($order->amount * 100).';l=uz;c='.$returnUrl;
        $base = Setting::get('payme_test_mode') ? 'https://test.paycom.uz' : 'https://checkout.paycom.uz';

        return $base.'/'.base64_encode($params);
    }

    public function handle(Request $request): array
    {
        $payload = $request->json()->all();
        $id = $payload['id'] ?? null;

        try {
            if (! $this->authorized($request)) {
                throw PaymeException::make(-32504, 'Ruxsat yo\'q', 'Недостаточно привилегий', 'Insufficient privilege');
            }
            $params = $payload['params'] ?? [];
            $result = match ($payload['method'] ?? '') {
                'CheckPerformTransaction' => $this->checkPerform($params),
                'CreateTransaction' => $this->create($params),
                'PerformTransaction' => $this->perform($params),
                'CancelTransaction' => $this->cancel($params),
                'CheckTransaction' => $this->check($params),
                'GetStatement' => $this->statement($params),
                default => throw PaymeException::make(-32601, 'Metod topilmadi', 'Метод не найден', 'Method not found'),
            };

            return ['jsonrpc' => '2.0', 'result' => $result, 'id' => $id];
        } catch (PaymeException $e) {
            return [
                'jsonrpc' => '2.0',
                'error' => ['code' => $e->rpcCode, 'message' => $e->rpcMessage, 'data' => $e->rpcData],
                'id' => $id,
            ];
        }
    }

    protected function authorized(Request $request): bool
    {
        $key = $this->secret();
        if (! $key) {
            return false;
        }
        $header = (string) $request->header('Authorization');
        if (! str_starts_with($header, 'Basic ')) {
            return false;
        }
        $decoded = base64_decode(substr($header, 6), true) ?: '';

        return hash_equals('Paycom:'.$key, $decoded);
    }

    /** Basic auth paroli (kalit). Platforma uchun — super admin sozlamalari. */
    protected function secret(): ?string
    {
        return Setting::get('payme_test_mode') ? Setting::get('payme_test_key') : Setting::get('payme_key');
    }

    protected function type(): string
    {
        return 'topup';
    }

    /** To'lov buyurtmasi (account.order_id bo'yicha). */
    protected function findOrder(int $id): ?object
    {
        return TopupOrder::find($id);
    }

    protected function payable(object $order): bool
    {
        return $order->status === 'new';
    }

    protected function amountSom(object $order): int
    {
        return (int) $order->amount;
    }

    /** Muvaffaqiyatli to'lovdan keyin buyurtmani bajarish (DB tranzaksiya ichida). */
    protected function fulfill(PaymeTransaction $tx, object $order): void
    {
        $order->update(['status' => 'paid']);
        $clinic = Clinic::find($tx->clinic_id);
        if ($clinic) {
            BillingService::adjust($clinic, (int) $order->amount, 'topup', "Payme to'lovi (#{$tx->payme_id})");
        }
    }

    /** Tranzaksiya tugagandan so'ng (DB tranzaksiyadan tashqarida). */
    protected function afterFulfill(PaymeTransaction $tx, object $order): void
    {
        $clinic = Clinic::find($tx->clinic_id);
        if ($clinic) {
            BillingService::afterTopup($clinic);
        }
    }

    /** Bajarilgan to'lovni bekor qilish. Mumkin bo'lmasa -31007 tashlaydi. */
    protected function refund(PaymeTransaction $tx, object $order, $reason): void
    {
        $clinic = Clinic::find($tx->clinic_id);
        if (! $clinic || $clinic->balance < (int) $order->amount) {
            throw PaymeException::make(-31007, 'Buyurtma bajarilgan, bekor qilib bo\'lmaydi', 'Заказ выполнен, отмена невозможна', 'Order completed, cannot cancel');
        }
        BillingService::adjust($clinic, -((int) $order->amount), 'refund', "Payme to'lovi bekor qilindi (#{$tx->payme_id})");
        $order->update(['status' => 'canceled']);
    }

    protected function onCanceledUnpaid(PaymeTransaction $tx): void
    {
    }

    protected function onCreated(PaymeTransaction $tx, object $order): void
    {
    }

    protected function nowMs(): int
    {
        return (int) round(microtime(true) * 1000);
    }

    protected function order(array $params): object
    {
        $order = $this->findOrder((int) ($params['account']['order_id'] ?? 0));
        if (! $order) {
            throw PaymeException::make(-31050, 'Buyurtma topilmadi', 'Заказ не найден', 'Order not found', 'order_id');
        }
        if (! $this->payable($order)) {
            throw PaymeException::make(-31051, 'Buyurtma to\'lash uchun yaroqsiz', 'Заказ недоступен для оплаты', 'Order is not payable', 'order_id');
        }
        if ((int) ($params['amount'] ?? 0) !== $this->amountSom($order) * 100) {
            throw PaymeException::make(-31001, 'Noto\'g\'ri summa', 'Неверная сумма', 'Wrong amount');
        }

        return $order;
    }

    protected function findOrderForTx(PaymeTransaction $tx): object
    {
        $order = $this->findOrder((int) $tx->topup_order_id);
        if (! $order) {
            throw PaymeException::make(-31050, 'Buyurtma topilmadi', 'Заказ не найден', 'Order not found', 'order_id');
        }

        return $order;
    }

    protected function tx(array $params): PaymeTransaction
    {
        $tx = PaymeTransaction::where('payme_id', $params['id'] ?? '')->where('order_type', $this->type())->first();
        if (! $tx) {
            throw PaymeException::make(-31003, 'Tranzaksiya topilmadi', 'Транзакция не найдена', 'Transaction not found');
        }

        return $tx;
    }

    protected function expired(PaymeTransaction $tx): bool
    {
        return $this->nowMs() - $tx->create_time > self::TIMEOUT_MS;
    }

    protected function cannotPerform(): PaymeException
    {
        return PaymeException::make(-31008, 'Amalni bajarib bo\'lmaydi', 'Невозможно выполнить операцию', 'Unable to perform operation');
    }

    protected function checkPerform(array $params): array
    {
        $this->order($params);

        return ['allow' => true];
    }

    protected function create(array $params): array
    {
        $tx = PaymeTransaction::where('payme_id', $params['id'] ?? '')->where('order_type', $this->type())->first();
        if ($tx) {
            if ($tx->state !== 1) {
                throw $this->cannotPerform();
            }
            if ($this->expired($tx)) {
                $tx->update(['state' => -1, 'reason' => 4, 'cancel_time' => $this->nowMs()]);
                throw $this->cannotPerform();
            }

            return ['create_time' => (int) $tx->create_time, 'transaction' => (string) $tx->id, 'state' => 1];
        }

        $order = $this->order($params);
        $busy = PaymeTransaction::where('order_type', $this->type())->where('topup_order_id', $order->id)->whereIn('state', [1, 2])->exists();
        if ($busy) {
            throw PaymeException::make(-31099, 'Buyurtma band', 'Заказ занят другой транзакцией', 'Order has another transaction', 'order_id');
        }

        $now = $this->nowMs();
        $tx = PaymeTransaction::create([
            'payme_id' => $params['id'],
            'topup_order_id' => $order->id,
            'clinic_id' => $order->clinic_id,
            'order_type' => $this->type(),
            'amount' => (int) $params['amount'],
            'state' => 1,
            'payme_time' => (int) ($params['time'] ?? $now),
            'create_time' => $now,
        ]);
        $this->onCreated($tx, $order);

        return ['create_time' => $now, 'transaction' => (string) $tx->id, 'state' => 1];
    }

    protected function perform(array $params): array
    {
        $tx = $this->tx($params);

        if ($tx->state === 2) {
            return ['transaction' => (string) $tx->id, 'perform_time' => (int) $tx->perform_time, 'state' => 2];
        }
        if ($tx->state !== 1) {
            throw $this->cannotPerform();
        }
        if ($this->expired($tx)) {
            $tx->update(['state' => -1, 'reason' => 4, 'cancel_time' => $this->nowMs()]);
            throw $this->cannotPerform();
        }

        $order = $this->findOrderForTx($tx);
        DB::transaction(function () use ($tx, $order) {
            $tx->update(['state' => 2, 'perform_time' => $this->nowMs()]);
            $this->fulfill($tx, $order);
        });
        $this->afterFulfill($tx, $order);

        return ['transaction' => (string) $tx->id, 'perform_time' => (int) $tx->perform_time, 'state' => 2];
    }

    protected function cancel(array $params): array
    {
        $tx = $this->tx($params);
        $reason = $params['reason'] ?? null;

        if ($tx->state === 1) {
            $tx->update(['state' => -1, 'reason' => $reason, 'cancel_time' => $this->nowMs()]);
            $this->onCanceledUnpaid($tx);
        } elseif ($tx->state === 2) {
            $order = $this->findOrderForTx($tx);
            DB::transaction(function () use ($tx, $order, $reason) {
                $this->refund($tx, $order, $reason);
                $tx->update(['state' => -2, 'reason' => $reason, 'cancel_time' => $this->nowMs()]);
            });
        }

        $tx->refresh();

        return ['transaction' => (string) $tx->id, 'cancel_time' => (int) $tx->cancel_time, 'state' => (int) $tx->state];
    }

    protected function check(array $params): array
    {
        $tx = $this->tx($params);

        return [
            'create_time' => (int) $tx->create_time,
            'perform_time' => (int) $tx->perform_time,
            'cancel_time' => (int) $tx->cancel_time,
            'transaction' => (string) $tx->id,
            'state' => (int) $tx->state,
            'reason' => $tx->reason === null ? null : (int) $tx->reason,
        ];
    }

    protected function statementQuery()
    {
        return PaymeTransaction::where('order_type', $this->type());
    }

    protected function statement(array $params): array
    {
        $rows = $this->statementQuery()->whereBetween('payme_time', [(int) ($params['from'] ?? 0), (int) ($params['to'] ?? 0)])
            ->orderBy('payme_time')->get();

        return ['transactions' => $rows->map(fn ($t) => [
            'id' => $t->payme_id,
            'time' => (int) $t->payme_time,
            'amount' => (int) $t->amount,
            'account' => ['order_id' => (string) $t->topup_order_id],
            'create_time' => (int) $t->create_time,
            'perform_time' => (int) $t->perform_time,
            'cancel_time' => (int) $t->cancel_time,
            'transaction' => (string) $t->id,
            'state' => (int) $t->state,
            'reason' => $t->reason === null ? null : (int) $t->reason,
        ])->all()];
    }
}
