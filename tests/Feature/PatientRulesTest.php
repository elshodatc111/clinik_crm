<?php

namespace Tests\Feature;

use App\Models\Patient;
use Tests\ClinicTestCase;

class PatientRulesTest extends ClinicTestCase
{
    protected function payload(array $over = []): array
    {
        return $over + ['last_name' => 'Yusupov', 'first_name' => 'Jasur', 'pinfl' => '12345678901234', 'birth_date' => '1990-05-05'];
    }

    public function test_same_pinfl_cannot_be_added_twice_in_one_clinic(): void
    {
        [$c, $u] = $this->makeClinic();
        $this->actingAs($u)->post(route('app.patients.store'), $this->payload())->assertRedirect();
        $this->actingAs($u)->post(route('app.patients.store'), $this->payload(['first_name' => 'Boshqa']))->assertSessionHasErrors('duplicate');

        $this->assertSame(1, Patient::withoutGlobalScopes()->where('clinic_id', $c->id)->count());
    }

    public function test_same_name_and_birth_date_is_duplicate(): void
    {
        [$c, $u] = $this->makeClinic();
        $this->actingAs($u)->post(route('app.patients.store'), $this->payload(['pinfl' => null]));
        $this->actingAs($u)->post(route('app.patients.store'), $this->payload(['pinfl' => null]))->assertSessionHasErrors('duplicate');

        $this->assertSame(1, Patient::withoutGlobalScopes()->count());
    }

    public function test_other_clinic_may_have_same_pinfl(): void
    {
        [$a, $ua] = $this->makeClinic();
        [$b, $ub] = $this->makeClinic();
        $this->actingAs($ua)->post(route('app.patients.store'), $this->payload());
        $this->actingAs($ub)->post(route('app.patients.store'), $this->payload())->assertSessionHasNoErrors();

        $this->assertSame(2, Patient::withoutGlobalScopes()->count());
    }

    public function test_only_super_admin_can_delete_patient(): void
    {
        [$c, $u] = $this->makeClinic();
        $p = $this->makePatient($c);

        $this->actingAs($u)->delete(route('admin.patients.destroy', $p->id))->assertForbidden();
        $this->assertNull(Patient::withoutGlobalScopes()->find($p->id)->deleted_at);

        $this->actingAs($this->superAdmin())->delete(route('admin.patients.destroy', $p->id))->assertRedirect();
        $this->assertNotNull(Patient::withoutGlobalScopes()->withTrashed()->find($p->id)->deleted_at);
    }
}
