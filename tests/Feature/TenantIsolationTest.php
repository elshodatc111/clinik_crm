<?php

namespace Tests\Feature;

use App\Models\Patient;
use Tests\ClinicTestCase;

class TenantIsolationTest extends ClinicTestCase
{
    public function test_clinic_cannot_open_other_clinics_patient(): void
    {
        [$a, $ua] = $this->makeClinic();
        [$b, $ub] = $this->makeClinic();
        $p = $this->makePatient($a);

        $this->actingAs($ub)->get(route('app.patients.show', $p->id))->assertNotFound();
        $this->actingAs($ua)->get(route('app.patients.show', $p->id))->assertOk();
    }

    public function test_patient_list_is_scoped_to_own_clinic(): void
    {
        [$a, $ua] = $this->makeClinic();
        [$b, $ub] = $this->makeClinic();
        $this->makePatient($a, ['last_name' => 'AAAA-Birinchi']);
        $this->makePatient($b, ['last_name' => 'BBBB-Ikkinchi']);

        $this->actingAs($ua)->get(route('app.patients.index'))->assertSee('AAAA-Birinchi')->assertDontSee('BBBB-Ikkinchi');
    }

    public function test_clinic_user_cannot_enter_admin_area(): void
    {
        [$a, $ua] = $this->makeClinic();
        $this->actingAs($ua)->get(route('admin.dashboard'))->assertForbidden();
    }
}
