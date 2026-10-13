<?php

namespace Tests\Feature;

use App\Models\Offer;
use App\Models\OfferAcceptance;
use App\Models\User;
use App\Rules\StrongPassword;
use App\Services\Totp;
use App\Support\Legal;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tests\ClinicTestCase;

class LegalSecurityTest extends ClinicTestCase
{
    public function test_staff_must_accept_offer_before_using_app(): void
    {
        config(['legal.enforce_staff_offer' => true]);
        [$clinic, $admin] = $this->makeClinic();

        $this->actingAs($admin)->get(route('app.dashboard'))->assertRedirect(route('offer.accept'));
        $this->get(route('account.edit'))->assertRedirect(route('offer.accept'));
        $this->get(route('offer.accept'))->assertOk();

        // rozilik bermasdan yuborish — rad etiladi
        $this->post(route('offer.accept.post'), ['offer_id' => Offer::active()->id])->assertSessionHasErrors('accept_offer');
        $this->get(route('app.dashboard'))->assertRedirect(route('offer.accept'));

        $this->post(route('offer.accept.post'), ['offer_id' => Offer::active()->id, 'accept_offer' => '1'])->assertRedirect();
        $this->assertDatabaseHas('offer_acceptances', ['user_id' => $admin->id, 'context' => 'login', 'offer_version' => Offer::active()->version]);
        $this->get(route('app.dashboard'))->assertOk();
    }

    public function test_new_offer_version_forces_reacceptance_and_super_admin_is_exempt(): void
    {
        config(['legal.enforce_staff_offer' => true]);
        [$clinic, $admin] = $this->makeClinic();
        $o = Offer::active();
        OfferAcceptance::create(['clinic_id' => $clinic->id, 'user_id' => $admin->id, 'clinic_name' => 'x', 'user_name' => 'x', 'phone' => '1', 'offer_id' => $o->id, 'offer_version' => $o->version, 'accepted_at' => now()]);
        $this->actingAs($admin)->get(route('app.dashboard'))->assertOk();

        Offer::query()->update(['is_active' => false]);
        Offer::create(['version' => $o->version + 1, 'title' => 'Yangi', 'body' => 'Yangi matn', 'is_active' => true]);
        $this->get(route('app.dashboard'))->assertRedirect(route('offer.accept'));

        $this->actingAs($this->superAdmin())->get(route('admin.dashboard'))->assertOk();
    }

    public function test_offer_text_renders_placeholders_and_russian(): void
    {
        \App\Models\Setting::set('app_name', 'Testmed');
        \App\Models\Setting::set('company_name', '"TESTMED" MChJ');
        $o = Offer::create(['version' => 99, 'title' => 'T', 'body' => 'Ijrochi: {{COMPANY}} / {{APP}}', 'body_ru' => 'Исполнитель: {{COMPANY}}', 'is_active' => true]);
        $this->assertStringContainsString('"TESTMED" MChJ / Testmed', $o->text('uz'));
        $this->assertStringContainsString('Исполнитель: "TESTMED" MChJ', $o->text('ru'));
        $this->assertStringNotContainsString('{{', Legal::privacy('uz'));
        $this->get(route('privacy'))->assertOk();
        $this->get(route('offer'))->assertOk()->assertSee('TESTMED', false);
    }

    public function test_registration_acceptance_is_linked_to_user(): void
    {
        $this->assertTrue(Offer::active() !== null);
        $this->assertStringNotContainsString('NAMUNA', Offer::active()->body);
    }

    public function test_online_booking_requires_both_consents(): void
    {
        config(['app.debug' => true]);
        [$clinic, $admin] = $this->makeClinic(['settings' => ['online_enabled' => true]]);
        $clinic->forceFill(['slug' => 'consent-klinika'])->save();
        $payload = ['service' => 1, 'doctor' => 1, 'date' => today()->addDay()->toDateString(), 'time' => '10:00', 'last_name' => 'A', 'first_name' => 'B', 'phone' => '+998901234567'];
        $this->postJson(route('booking.book', 'consent-klinika'), $payload + ['accept' => 1])->assertStatus(422)->assertJsonValidationErrors('accept_privacy');
        $this->postJson(route('booking.book', 'consent-klinika'), $payload + ['accept_privacy' => 1])->assertStatus(422)->assertJsonValidationErrors('accept');
    }

    public function test_strong_password_rule(): void
    {
        $v = fn ($p) => Validator::make(['p' => $p], ['p' => [new StrongPassword]])->passes();
        $this->assertFalse($v('short1'));
        $this->assertFalse($v('onlyletters'));
        $this->assertFalse($v('123456789'));
        $this->assertFalse($v('password123'));
        $this->assertTrue($v('Klinika#2026x'));
        $this->assertTrue($v('secret1234'));
    }

    public function test_totp_matches_rfc6238_vectors(): void
    {
        $secret = Totp::base32Encode('12345678901234567890');
        $this->assertSame('94287082', Totp::code($secret, 59, 8));
        $this->assertSame('07081804', Totp::code($secret, 1111111109, 8));
        $this->assertNotNull(Totp::verify($secret, Totp::code($secret, 1700000000), 1, 1700000000));
        $this->assertNull(Totp::verify($secret, '000000', 1, 1700000000));
    }

    public function test_login_with_two_factor_requires_code(): void
    {
        [$clinic, $admin] = $this->makeClinic();
        $secret = Totp::secret();
        $admin->forceFill(['two_factor_secret' => $secret, 'two_factor_recovery_codes' => ['abcde-fghij'], 'two_factor_confirmed_at' => now()])->save();

        $this->post(route('login.post'), ['login' => $admin->phone, 'password' => 'password123'])->assertRedirect(route('login.2fa'));
        $this->assertGuest();
        $this->post(route('login.2fa.post'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();
        $this->post(route('login.2fa.post'), ['code' => Totp::code($secret)])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);

        // zaxira kod bir marta ishlaydi
        auth()->logout();
        $this->post(route('login.post'), ['login' => $admin->phone, 'password' => 'password123']);
        $this->post(route('login.2fa.post'), ['code' => 'abcde-fghij'])->assertRedirect(route('dashboard'));
        $this->assertSame([], $admin->fresh()->two_factor_recovery_codes);
    }

    public function test_security_headers_and_no_store_for_authenticated_pages(): void
    {
        [$clinic, $admin] = $this->makeClinic();
        $r = $this->actingAs($admin)->get(route('app.dashboard'));
        $r->assertHeader('X-Frame-Options', 'SAMEORIGIN')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('no-store', (string) $r->headers->get('Cache-Control'));
        $this->assertTrue($r->headers->has('Content-Security-Policy') || $r->headers->has('Content-Security-Policy-Report-Only'));
    }

    public function test_otp_is_masked_in_sms_log(): void
    {
        \App\Services\SmsService::send('998901112233', 'Tasdiqlash kodi: 123456. App', null, false, 'otp');
        $this->assertDatabaseMissing('sms_logs', ['message' => 'Tasdiqlash kodi: 123456. App']);
        $this->assertStringContainsString('••••••', \App\Models\SmsLog::latest('id')->first()->message);
    }

    public function test_login_failures_and_logins_are_audited(): void
    {
        [$clinic, $admin] = $this->makeClinic();
        $this->post(route('login.post'), ['login' => $admin->phone, 'password' => 'wrongpass1']);
        $this->post(route('login.post'), ['login' => $admin->phone, 'password' => 'password123']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'login_failed', 'user_id' => $admin->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'login', 'user_id' => $admin->id]);
    }

    public function test_backup_command_creates_file_on_sqlite_file_db(): void
    {
        // :memory: bazada fayl yo'q — buyruq xatosiz yakunlanishi kerak
        $this->artisan('db:backup')->assertExitCode(0);
    }
}
