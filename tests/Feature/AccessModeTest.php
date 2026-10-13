<?php

namespace Tests\Feature;

use App\Models\Patient;
use Tests\ClinicTestCase;

class AccessModeTest extends ClinicTestCase
{
    public function test_view_only_clinic_can_read_but_not_write(): void
    {
        [$c, $u] = $this->makeClinic(['status' => 'view_only', 'paid_until' => now()->subDays(3)]);

        $this->actingAs($u)->get(route('app.patients.index'))->assertOk();
        $this->actingAs($u)->post(route('app.patients.store'), ['last_name' => 'Test', 'first_name' => 'Test'])->assertSessionHas('error');
        $this->assertSame(0, Patient::withoutGlobalScopes()->count());
    }

    public function test_blocked_clinic_is_redirected_to_billing(): void
    {
        [$c, $u] = $this->makeClinic(['status' => 'blocked', 'blocked_at' => now()]);

        $this->actingAs($u)->get(route('app.patients.index'))->assertRedirect(route('app.billing'));
        $this->actingAs($u)->get(route('app.billing'))->assertOk();
    }

    public function test_demo_banner_is_shown(): void
    {
        [$c, $u] = $this->makeClinic();
        $this->actingAs($u)->get(route('app.dashboard'))->assertOk()->assertSee('Demo versiya');
    }
}
