<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\TopupOrder;
use Tests\ClinicTestCase;

class PaymeTest extends ClinicTestCase
{
    protected function rpc(string $method, array $params, bool $auth = true)
    {
        $headers = $auth ? ['Authorization' => 'Basic '.base64_encode('Paycom:test-secret')] : [];

        return $this->postJson('/payme', ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params], $headers);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('payme_merchant_id', 'merchant123');
        Setting::set('payme_key', 'test-secret');
    }

    public function test_requests_without_credentials_are_rejected(): void
    {
        $this->rpc('CheckPerformTransaction', [], false)->assertJsonPath('error.code', -32504);
    }

    public function test_full_payment_flow_credits_balance(): void
    {
        [$c] = $this->makeClinic();
        $order = TopupOrder::create(['clinic_id' => $c->id, 'amount' => 500000, 'status' => 'new']);
        $account = ['order_id' => $order->id];

        $this->rpc('CheckPerformTransaction', ['amount' => 50000000, 'account' => $account])->assertJsonPath('result.allow', true);

        $time = (int) round(microtime(true) * 1000);
        $this->rpc('CreateTransaction', ['id' => 'tx1', 'time' => $time, 'amount' => 50000000, 'account' => $account])->assertJsonPath('result.state', 1);
        // takroriy chaqiruv xuddi shu natijani qaytaradi
        $this->rpc('CreateTransaction', ['id' => 'tx1', 'time' => $time, 'amount' => 50000000, 'account' => $account])->assertJsonPath('result.state', 1);

        $this->rpc('PerformTransaction', ['id' => 'tx1'])->assertJsonPath('result.state', 2);
        $this->assertSame(500000, (int) $c->fresh()->balance);

        // ikkinchi marta bajarilsa balans ikki marta oshmaydi
        $this->rpc('PerformTransaction', ['id' => 'tx1'])->assertJsonPath('result.state', 2);
        $this->assertSame(500000, (int) $c->fresh()->balance);
    }

    public function test_wrong_amount_is_rejected(): void
    {
        [$c] = $this->makeClinic();
        $order = TopupOrder::create(['clinic_id' => $c->id, 'amount' => 500000, 'status' => 'new']);

        $this->rpc('CheckPerformTransaction', ['amount' => 100, 'account' => ['order_id' => $order->id]])->assertJsonPath('error.code', -31001);
    }

    public function test_unknown_order_is_rejected(): void
    {
        $this->rpc('CheckPerformTransaction', ['amount' => 100, 'account' => ['order_id' => 99999]])->assertJsonPath('error.code', -31050);
    }

    public function test_cancel_refunds_performed_payment(): void
    {
        [$c] = $this->makeClinic();
        $order = TopupOrder::create(['clinic_id' => $c->id, 'amount' => 100000, 'status' => 'new']);
        $account = ['order_id' => $order->id];
        $time = (int) round(microtime(true) * 1000);
        $this->rpc('CreateTransaction', ['id' => 'tx2', 'time' => $time, 'amount' => 10000000, 'account' => $account]);
        $this->rpc('PerformTransaction', ['id' => 'tx2']);

        $this->rpc('CancelTransaction', ['id' => 'tx2', 'reason' => 5])->assertJsonPath('result.state', -2);
        $this->assertSame(0, (int) $c->fresh()->balance);
    }
}
