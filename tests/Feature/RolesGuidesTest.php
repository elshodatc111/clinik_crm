<?php

namespace Tests\Feature;

use App\Models\LabOrder;
use App\Models\User;
use Tests\ClinicTestCase;

class RolesGuidesTest extends ClinicTestCase
{
    protected function staff($clinic, string $role, array $extra = []): User
    {
        return User::create($extra + [
            'clinic_id' => $clinic->id, 'name' => ucfirst($role), 'phone' => $this->phone(), 'password' => 'password123',
            'role' => $role, 'is_active' => true,
        ]);
    }

    public function test_extra_role_grants_access(): void
    {
        [$clinic] = $this->makeClinic();
        $cashier = $this->staff($clinic, 'cashier');
        $this->actingAs($cashier)->get(route('app.patients.create'))->assertForbidden();

        $cashier->update(['extra_roles' => ['receptionist']]);
        $this->actingAs($cashier->fresh())->get(route('app.patients.create'))->assertOk();
    }

    public function test_only_director_can_delete_lab_order(): void
    {
        [$clinic, $director] = $this->makeClinic();
        $admin = $this->staff($clinic, 'clinic_admin', ['position' => 'admin']);
        $patient = $this->makePatient($clinic);
        $lab = LabOrder::create(['clinic_id' => $clinic->id, 'patient_id' => $patient->id, 'test_name' => 'Qon', 'status' => 'ordered', 'ordered_at' => now()]);

        $this->actingAs($admin)->delete(route('app.lab.destroy', $lab))->assertForbidden();
        $this->assertDatabaseHas('lab_orders', ['id' => $lab->id]);

        $this->actingAs($director)->delete(route('app.lab.destroy', $lab))->assertRedirect();
        $this->assertDatabaseMissing('lab_orders', ['id' => $lab->id]);
    }

    public function test_guides_are_limited_by_role(): void
    {
        [$clinic, $director] = $this->makeClinic();
        $cashier = $this->staff($clinic, 'cashier');

        $this->actingAs($cashier)->get(route('app.guides.index'))->assertRedirect(route('app.guides.show', 'cashier'));
        $this->actingAs($cashier)->get(route('app.guides.show', 'cashier'))->assertOk();
        $this->actingAs($cashier)->get(route('app.guides.show', 'director'))->assertNotFound();

        $this->actingAs($director)->get(route('app.guides.index'))->assertOk()->assertSee(route('app.guides.show', 'doctor'));
    }

    public function test_admin_cannot_edit_director(): void
    {
        [$clinic, $director] = $this->makeClinic();
        $admin = $this->staff($clinic, 'clinic_admin', ['position' => 'admin']);

        $this->actingAs($admin)->get(route('app.staff.edit', $director))->assertForbidden();
        $this->actingAs($director)->get(route('app.staff.edit', $admin))->assertOk();
    }

    public function test_landing_shows_contacts_from_settings(): void
    {
        \App\Models\Setting::set('support_phone', '+998 71 000 00 00');
        \App\Models\Setting::set('contact_address', 'Toshkent, Test ko\'chasi');
        $this->get('/')->assertOk()->assertSee('+998 71 000 00 00')->assertSee('Test ko');
    }
}
