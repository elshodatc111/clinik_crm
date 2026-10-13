<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\OfferAcceptance;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\ClinicTestCase;

class RegistrationTest extends ClinicTestCase
{
    protected function data(array $over = []): array
    {
        return $over + [
            'clinic_name' => 'Shifo klinikasi', 'owner_name' => 'Aliyev Vali', 'phone' => '+998 90 123 45 67',
            'email' => 'vali@example.com', 'password' => 'secret1234', 'password_confirmation' => 'secret1234', 'accept_offer' => '1',
        ];
    }

    public function test_landing_page_opens(): void
    {
        $this->get('/')->assertOk()->assertSee('Tariflar');
    }

    public function test_cannot_register_without_accepting_offer(): void
    {
        $this->post(route('register.post'), $this->data(['accept_offer' => null]))->assertSessionHasErrors('accept_offer');
        $this->assertSame(0, Clinic::count());
    }

    public function test_register_sends_code(): void
    {
        $this->post(route('register.post'), $this->data())->assertRedirect(route('register.verify'));
        $this->assertDatabaseHas('sms_codes', ['phone' => '998901234567']);
    }

    public function test_verify_creates_demo_clinic_and_offer_acceptance(): void
    {
        $this->post(route('register.post'), $this->data());
        DB::table('sms_codes')->where('phone', '998901234567')->update(['code' => Hash::make('123456')]);
        $session = ['reg' => [
            'clinic_name' => 'Shifo klinikasi', 'owner_name' => 'Aliyev Vali', 'phone' => '998901234567', 'email' => 'vali@example.com',
            'password' => Hash::make('secret1234'), 'offer_id' => \App\Models\Offer::active()->id,
        ]];

        $this->withSession($session)->post(route('register.verify.post'), ['code' => '123456'])->assertRedirect(route('app.profile.edit'));

        $clinic = Clinic::where('phone', '998901234567')->firstOrFail();
        $this->assertSame('trial', $clinic->status);
        $this->assertTrue($clinic->trial_ends_at->between(now()->addDays(13), now()->addDays(15)));
        $this->assertSame('clinic_admin', User::where('clinic_id', $clinic->id)->first()->role);
        $this->assertSame(1, OfferAcceptance::where('clinic_id', $clinic->id)->count());
    }

    public function test_wrong_code_is_rejected(): void
    {
        $this->post(route('register.post'), $this->data());
        $session = ['reg' => ['clinic_name' => 'X', 'owner_name' => 'Y', 'phone' => '998901234567', 'email' => null, 'password' => Hash::make('secret1234'), 'offer_id' => 1]];

        $this->withSession($session)->post(route('register.verify.post'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertSame(0, Clinic::count());
    }

    public function test_login_redirects_by_role(): void
    {
        [$c, $u] = $this->makeClinic();
        $this->post(route('login.post'), ['login' => $u->phone, 'password' => 'password123'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($u);
    }
}
