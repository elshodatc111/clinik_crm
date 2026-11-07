<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\OtpTelegramLink;
use App\Models\OtpTelegramRequest;
use App\Models\Setting;
use App\Models\User;
use App\Services\OtpCodes;
use App\Services\TelegramOtp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\ClinicTestCase;

class TelegramOtpTest extends ClinicTestCase
{
    private const PHONE = '998901234567';

    protected function bot(): void
    {
        Setting::set('tg_bot_token', '123456:ABCDEFGHIJKLMNOPQRSTUVWXYZ012345');
        Setting::set('tg_bot_username', 'klinika_test_bot');
        Setting::set('tg_bot_status', 'ok');
    }

    protected function reg(): array
    {
        return ['reg' => ['clinic_name' => 'Shifo', 'owner_name' => 'Aliyev', 'phone' => self::PHONE, 'email' => null,
            'password' => Hash::make('secret1234'), 'offer_id' => \App\Models\Offer::active()->id]];
    }

    protected function hook(array $update)
    {
        return $this->postJson(route('telegram.webhook'), $update, ['X-Telegram-Bot-Api-Secret-Token' => TelegramOtp::webhookSecret()]);
    }

    public function test_register_with_bot_does_not_send_sms_and_shows_telegram(): void
    {
        $this->bot();
        Http::fake();
        $this->post(route('register.post'), ['clinic_name' => 'Shifo', 'owner_name' => 'Aliyev', 'phone' => '+998 90 123 45 67', 'email' => null,
            'password' => 'secret1234', 'password_confirmation' => 'secret1234', 'accept_offer' => '1'])->assertRedirect(route('register.verify'));
        $this->assertDatabaseMissing('sms_codes', ['phone' => self::PHONE]);
        Http::assertNothingSent();
        $this->withSession($this->reg())->get(route('register.verify'))->assertOk()->assertSee('Telegram')->assertDontSee(route('register.resend'), false);
    }

    public function test_request_creates_start_link_and_status_flow(): void
    {
        $this->bot();
        $res = $this->withSession($this->reg())->postJson(route('otp.telegram'), ['flow' => 'register'])->assertOk()->json();
        $this->assertTrue($res['ok']);
        $this->assertFalse($res['sent']);
        $this->assertStringStartsWith('https://t.me/klinika_test_bot?start=o_', $res['link']);

        $this->withSession($this->reg())->getJson(route('otp.telegram.status', ['token' => $res['token']]))->assertJson(['state' => 'pending']);
        // token xom holda saqlanmaydi
        $this->assertDatabaseMissing('otp_telegram_requests', ['token_hash' => $res['token']]);
    }

    public function test_full_flow_start_contact_sends_code_and_verify_creates_clinic(): void
    {
        $this->bot();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []])]);
        $res = $this->withSession($this->reg())->postJson(route('otp.telegram'), ['flow' => 'register'])->json();
        $token = substr($res['link'], strpos($res['link'], 'o_') + 2);

        $this->hook(['message' => ['chat' => ['id' => 777, 'type' => 'private'], 'from' => ['id' => 55], 'text' => '/start o_'.$token]])->assertOk();
        $this->assertSame('awaiting_contact', OtpTelegramRequest::first()->state);

        $this->hook(['message' => ['chat' => ['id' => 777, 'type' => 'private'], 'from' => ['id' => 55, 'username' => 'ali'],
            'contact' => ['phone_number' => '+998901234567', 'user_id' => 55]]])->assertOk();

        $this->assertSame('sent', OtpTelegramRequest::first()->state);
        $this->assertSame(777, (int) OtpTelegramLink::where('phone', self::PHONE)->value('chat_id'));
        $this->assertDatabaseHas('sms_codes', ['phone' => self::PHONE, 'purpose' => 'register', 'channel' => 'telegram']);
        $sentCode = null;
        Http::assertSent(function ($r) use (&$sentCode) {
            if (str_contains($r->url(), 'sendMessage') && preg_match('/(\d{6})/', (string) $r['text'], $m) && ($r['chat_id'] ?? 0) === 777) {
                $sentCode = $m[1];

                return true;
            }

            return false;
        });
        $this->assertNotNull($sentCode);

        $this->withSession($this->reg())->post(route('register.verify.post'), ['code' => $sentCode])->assertRedirect(route('app.profile.edit'));
        $this->assertNotNull(Clinic::where('phone', self::PHONE)->first());
        $this->assertNotNull(User::where('phone', self::PHONE)->value('phone_verified_at'));
    }

    public function test_foreign_contact_is_rejected(): void
    {
        $this->bot();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $res = $this->withSession($this->reg())->postJson(route('otp.telegram'), ['flow' => 'register'])->json();
        $token = substr($res['link'], strpos($res['link'], 'o_') + 2);
        $this->hook(['message' => ['chat' => ['id' => 9, 'type' => 'private'], 'from' => ['id' => 1], 'text' => '/start o_'.$token]]);

        // boshqa odamning kontakti (user_id mos emas)
        $this->hook(['message' => ['chat' => ['id' => 9, 'type' => 'private'], 'from' => ['id' => 1], 'contact' => ['phone_number' => self::PHONE, 'user_id' => 2]]]);
        $this->assertSame('awaiting_contact', OtpTelegramRequest::first()->state);
        $this->assertSame(0, OtpTelegramLink::count());

        // o'zining kontakti, lekin raqam mos emas
        $this->hook(['message' => ['chat' => ['id' => 9, 'type' => 'private'], 'from' => ['id' => 1], 'contact' => ['phone_number' => '998909999999', 'user_id' => 1]]]);
        $this->assertSame('failed', OtpTelegramRequest::first()->state);
        $this->assertSame(0, OtpTelegramLink::count());
        $this->assertDatabaseMissing('sms_codes', ['phone' => self::PHONE]);
    }

    public function test_webhook_requires_secret(): void
    {
        $this->bot();
        $this->postJson(route('telegram.webhook'), ['message' => []])->assertForbidden();
        $this->postJson(route('telegram.webhook'), ['message' => []], ['X-Telegram-Bot-Api-Secret-Token' => 'wrong'])->assertForbidden();
    }

    public function test_known_chat_gets_code_immediately(): void
    {
        $this->bot();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        OtpTelegramLink::create(['phone' => self::PHONE, 'chat_id' => 321, 'verified_at' => now()]);
        $res = $this->withSession($this->reg())->postJson(route('otp.telegram'), ['flow' => 'register'])->json();
        $this->assertTrue($res['sent']);
        $this->assertDatabaseHas('sms_codes', ['phone' => self::PHONE, 'channel' => 'telegram']);
    }

    public function test_register_falls_back_to_sms_when_bot_not_configured(): void
    {
        $this->post(route('register.post'), ['clinic_name' => 'Shifo', 'owner_name' => 'Aliyev', 'phone' => '+998 90 123 45 67', 'email' => null,
            'password' => 'secret1234', 'password_confirmation' => 'secret1234', 'accept_offer' => '1'])->assertRedirect(route('register.verify'));
        $this->assertDatabaseHas('sms_codes', ['phone' => self::PHONE, 'purpose' => 'register', 'channel' => 'sms']);
    }

    public function test_flow_phone_comes_from_session_not_client(): void
    {
        $this->bot();
        $this->postJson(route('otp.telegram'), ['flow' => 'register', 'phone' => self::PHONE])->assertStatus(422);
    }

    // ---------- Parolni tiklash ----------

    public function test_password_reset_with_telegram_code(): void
    {
        $this->bot();
        [$clinic, $user] = $this->makeClinic();
        $user->update(['phone' => self::PHONE]);
        $this->get(route('password.forgot'))->assertOk();

        $this->post(route('password.forgot.post'), ['phone' => '+998 90 123 45 67'])->assertRedirect(route('password.verify'));
        $this->assertSame(self::PHONE, session('reset.phone'));

        $code = OtpCodes::issue(self::PHONE, 'reset', 'telegram');
        $this->post(route('password.reset'), ['code' => '000000', 'password' => 'Yangi-parol-77', 'password_confirmation' => 'Yangi-parol-77'])->assertSessionHasErrors('code');
        $this->post(route('password.reset'), ['code' => $code, 'password' => 'Yangi-parol-77', 'password_confirmation' => 'Yangi-parol-77'])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('Yangi-parol-77', $user->fresh()->password));
        $this->assertDatabaseMissing('sms_codes', ['phone' => self::PHONE, 'purpose' => 'reset']);
    }

    public function test_reset_code_is_not_valid_for_registration_and_vice_versa(): void
    {
        $code = OtpCodes::issue(self::PHONE, 'reset', 'telegram');
        $this->assertNotNull(OtpCodes::check(self::PHONE, 'register', $code));
        $this->assertNull(OtpCodes::check(self::PHONE, 'reset', $code));
        $this->assertNotNull(OtpCodes::check(self::PHONE, 'reset', $code), 'kod bir martalik');
    }

    public function test_reset_does_not_reveal_unknown_phone_and_sends_nothing(): void
    {
        $this->bot();
        Http::fake();
        $this->post(route('password.forgot.post'), ['phone' => '+998 90 000 00 00'])->assertRedirect(route('password.verify'));
        Http::assertNothingSent();
        $this->assertSame(0, DB::table('sms_codes')->count());
    }

    public function test_code_locks_after_max_attempts(): void
    {
        OtpCodes::issue(self::PHONE, 'reset', 'sms');
        for ($i = 0; $i < OtpCodes::MAX_ATTEMPTS; $i++) {
            OtpCodes::check(self::PHONE, 'reset', '111111');
        }
        $this->assertNotNull(OtpCodes::check(self::PHONE, 'reset', '111111'));
    }

    public function test_reset_verify_page_renders_in_both_modes(): void
    {
        $this->withSession(['reset' => ['phone' => self::PHONE]])->get(route('password.verify'))->assertOk()->assertSee('SMS kod yuborildi');
        $this->bot();
        $this->withSession(['reset' => ['phone' => self::PHONE]])->get(route('password.verify'))->assertOk()->assertSee('x-data="tgOtp(\'reset\'', false);
        $this->flushSession();
        $this->get(route('password.verify'))->assertRedirect(route('password.forgot'));
    }

    public function test_admin_settings_page_renders_with_bot_and_gateway(): void
    {
        $this->bot();
        Setting::set('sms_gateway_key', 'secret-key');
        $this->actingAs($this->superAdmin())->get(route('admin.settings.edit'))->assertOk()
            ->assertSee('klinika_test_bot')->assertSee('SMS Gateway (USB modem)')->assertDontSee('secret-key')->assertDontSee('ABCDEFGHIJKLMNOPQRSTUVWXYZ012345');
    }

    public function test_login_page_links_to_reset(): void
    {
        $this->get(route('login'))->assertSee(route('password.forgot'), false);
    }

    // ---------- Admin ----------

    public function test_admin_connects_bot_and_sets_webhook(): void
    {
        Http::fake([
            'api.telegram.org/*/getMe' => Http::response(['ok' => true, 'result' => ['username' => 'klinika_bot']]),
            'api.telegram.org/*/setWebhook' => Http::response(['ok' => true]),
        ]);
        \Illuminate\Support\Facades\URL::forceRootUrl('http://klinika.test');
        $this->actingAs($this->superAdmin())
            ->post(route('admin.settings.telegram'), ['action' => 'connect', 'tg_bot_token' => '123456:ABCDEFGHIJKLMNOPQRSTUVWXYZ012345'])
            ->assertSessionHas('success');
        $this->assertSame('klinika_bot', Setting::get('tg_bot_username'));
        $this->assertSame('ok', Setting::get('tg_bot_status'));
        Http::assertSent(fn ($r) => str_contains($r->url(), 'setWebhook') && $r['url'] === 'https://klinika.test/telegram/webhook' && $r['secret_token'] === TelegramOtp::webhookSecret());
    }

    public function test_clinic_admin_cannot_touch_bot_settings(): void
    {
        [$c, $u] = $this->makeClinic();
        $this->actingAs($u)->post(route('admin.settings.telegram'), ['action' => 'disconnect'])->assertForbidden();
    }
}
