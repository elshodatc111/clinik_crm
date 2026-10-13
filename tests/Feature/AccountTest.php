<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Tests\ClinicTestCase;

class AccountTest extends ClinicTestCase
{
    public function test_every_user_can_open_profile_and_pages_show_logout_confirm(): void
    {
        [$clinic, $admin] = $this->makeClinic();
        $this->actingAs($admin)->get(route('account.edit'))->assertOk()->assertSee($admin->name);
        $this->get(route('app.dashboard'))->assertOk()->assertSee('confirm-logout', false);
        $this->actingAs($this->superAdmin())->get(route('account.edit'))->assertOk();
    }

    public function test_password_change_requires_current_password(): void
    {
        [$clinic, $admin] = $this->makeClinic();
        $this->actingAs($admin)->put(route('account.password'), ['current_password' => 'wrong-pass', 'password' => 'NewPass#2026', 'password_confirmation' => 'NewPass#2026'])
            ->assertSessionHasErrors('current_password', null, 'password');

        $old = $admin->fresh()->password;
        $this->put(route('account.password'), ['current_password' => 'password123', 'password' => 'NewPass#2026', 'password_confirmation' => 'NewPass#2026'])->assertRedirect(route('account.edit'));
        $this->assertTrue(Hash::check('NewPass#2026', $admin->fresh()->password));
        $this->assertNotSame($old, $admin->fresh()->password);
    }

    public function test_changing_phone_needs_password_but_name_does_not(): void
    {
        [$clinic, $admin] = $this->makeClinic();
        $base = ['name' => 'Yangi Ism', 'phone' => $admin->phone, 'locale' => 'uz'];
        $this->actingAs($admin)->put(route('account.update'), $base)->assertRedirect();
        $this->assertSame('Yangi Ism', $admin->fresh()->name);

        $this->put(route('account.update'), ['phone' => '+998901119988'] + $base)->assertSessionHasErrors('current_password');
        $this->put(route('account.update'), ['phone' => '+998901119988', 'current_password' => 'password123'] + $base)->assertSessionHasNoErrors();
        $this->assertSame('998901119988', $admin->fresh()->phone);
    }
}
