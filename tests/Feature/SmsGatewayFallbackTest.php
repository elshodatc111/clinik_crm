<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\SmsLog;
use App\Services\SmsService;
use Illuminate\Support\Facades\Http;
use Tests\ClinicTestCase;

class SmsGatewayFallbackTest extends ClinicTestCase
{
    private const PHONE = '998901234567';

    protected function gw(): void
    {
        Setting::set('sms_gateway_url', 'https://gw.test');
        Setting::set('sms_gateway_key', 'k');
        Setting::set('eskiz_email', 'a@b.c');
        Setting::set('eskiz_password', 'p');
    }

    protected function eskizFake(): array
    {
        return [
            'notify.eskiz.uz/api/auth/login' => Http::response(['data' => ['token' => 't']]),
            'notify.eskiz.uz/api/message/sms/send' => Http::response(['id' => 1]),
        ];
    }

    public function test_platform_sms_goes_through_modem_when_healthy(): void
    {
        $this->gw();
        Http::fake($this->eskizFake() + [
            'gw.test/api/v1/health' => Http::response(['status' => 'ok', 'modems_usable' => 1]),
            'gw.test/api/v1/sms/send' => Http::response(['id' => 5, 'status' => 'sent'], 202),
        ]);
        $this->assertTrue(SmsService::send(self::PHONE, 'salom', null, false, 'test'));
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'eskiz'));
        $this->assertSame('sent', SmsLog::latest('id')->first()->status);
    }

    public function test_falls_back_to_eskiz_when_modem_down(): void
    {
        $this->gw();
        Http::fake($this->eskizFake() + ['gw.test/api/v1/health' => Http::response(['status' => 'down', 'modems_usable' => 0])]);
        $this->assertTrue(SmsService::send(self::PHONE, 'salom', null, false, 'test'));
        Http::assertSent(fn ($r) => str_contains($r->url(), 'message/sms/send'));
        $this->assertStringContainsString('Gateway', (string) SmsLog::latest('id')->first()->response);
    }

    public function test_falls_back_to_eskiz_when_gateway_unreachable(): void
    {
        $this->gw();
        Http::fake($this->eskizFake() + ['gw.test/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout')]);
        $this->assertTrue(SmsService::send(self::PHONE, 'salom', null, false, 'test'));
    }

    public function test_invalid_phone_error_does_not_double_send_via_eskiz(): void
    {
        $this->gw();
        Http::fake($this->eskizFake() + [
            'gw.test/api/v1/health' => Http::response(['status' => 'ok', 'modems_usable' => 1]),
            'gw.test/api/v1/sms/send' => Http::response(['error' => ['code' => 'invalid_phone', 'message' => 'x']], 422),
        ]);
        $this->assertFalse(SmsService::send(self::PHONE, 'salom', null, false, 'test'));
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'message/sms/send'));
    }

    public function test_queued_message_is_cancelled_and_sent_via_eskiz(): void
    {
        $this->gw();
        Setting::set('sms_gateway_wait', 0);
        Http::fake($this->eskizFake() + [
            'gw.test/api/v1/health' => Http::response(['status' => 'ok', 'modems_usable' => 1]),
            'gw.test/api/v1/sms/send' => Http::response(['id' => 9, 'status' => 'queued'], 202),
            'gw.test/api/v1/sms/9' => Http::response(['status' => 'cancelled']),
        ]);
        $this->assertTrue(SmsService::send(self::PHONE, 'salom', null, false, 'test'));
        Http::assertSent(fn ($r) => $r->method() === 'DELETE' && str_contains($r->url(), '/sms/9'));
        Http::assertSent(fn ($r) => str_contains($r->url(), 'message/sms/send'));
    }

    public function test_clinic_patient_sms_never_uses_modem(): void
    {
        $this->gw();
        [$clinic] = $this->makeClinic(['settings' => ['eskiz_email' => 'c@c.c', 'eskiz_password' => \Illuminate\Support\Facades\Crypt::encryptString('pw')]]);
        Http::fake($this->eskizFake() + ['gw.test/*' => Http::response(['status' => 'ok', 'modems_usable' => 1])]);
        $this->assertTrue(SmsService::send(self::PHONE, 'eslatma', $clinic->id, true, 'reminder'));
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'gw.test'));
    }

    public function test_gateway_disabled_goes_straight_to_eskiz(): void
    {
        $this->gw();
        Setting::set('sms_gateway_on', '0');
        Http::fake($this->eskizFake());
        $this->assertTrue(SmsService::send(self::PHONE, 'salom', null, false, 'test'));
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'gw.test'));
    }

    public function test_admin_gateway_check(): void
    {
        $this->gw();
        Http::fake(['gw.test/api/v1/health' => Http::response(['status' => 'ok', 'modems_usable' => 2])]);
        $this->actingAs($this->superAdmin())->post(route('admin.settings.gateway-check'))->assertSessionHas('success');
    }
}
