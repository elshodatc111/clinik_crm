<?php

namespace Tests\Feature;

use App\Models\CashMovement;
use App\Models\Invoice;
use App\Services\PatientBalance;
use Tests\ClinicTestCase;

class PatientBalanceTest extends ClinicTestCase
{
    private function invoice($clinic, $patient, $admin, int $total = 100000): Invoice
    {
        return Invoice::create([
            'clinic_id' => $clinic->id, 'patient_id' => $patient->id, 'number' => Invoice::nextNumber($clinic->id),
            'total' => $total, 'discount' => 0, 'paid' => 0, 'status' => 'unpaid', 'created_by' => $admin->id,
        ]);
    }

    public function test_overpay_becomes_credit_and_debt_is_tracked(): void
    {
        [$clinic, $admin] = $this->makeClinic();
        $p = $this->makePatient($clinic);
        $inv = $this->invoice($clinic, $p, $admin);

        $this->actingAs($admin);
        $this->assertSame(100000, PatientBalance::forPatient($p->id)['debt']);

        $this->post(route('app.invoices.pay', $inv), ['amount' => 130000, 'method' => 'cash'])->assertSessionHasErrors();
        $this->post(route('app.invoices.pay', $inv), ['amount' => 130000, 'method' => 'cash', 'overpay' => 1])->assertRedirect();

        $b = PatientBalance::forPatient($p->id);
        $this->assertSame(0, $b['debt']);
        $this->assertSame(30000, $b['credit']);
        $this->assertSame('paid', $inv->fresh()->status);
    }

    public function test_apply_credit_and_refund(): void
    {
        [$clinic, $admin] = $this->makeClinic();
        $p = $this->makePatient($clinic);
        $this->actingAs($admin);

        $this->post(route('app.patients.deposit', $p), ['amount' => 200000, 'method' => 'cash'])->assertRedirect();
        $this->assertSame(200000, PatientBalance::forPatient($p->id)['credit']);

        $inv = $this->invoice($clinic, $p, $admin, 150000);
        $this->post(route('app.invoices.apply-credit', $inv), ['amount' => 150000])->assertRedirect();
        $this->assertSame('paid', $inv->fresh()->status);
        $this->assertSame(50000, PatientBalance::forPatient($p->id)['credit']);

        $this->post(route('app.patients.refund', $p), ['amount' => 50000, 'method' => 'cash'])->assertRedirect();
        $this->assertSame(0, PatientBalance::forPatient($p->id)['credit']);
        $this->assertDatabaseHas('cash_movements', ['category' => 'refund', 'amount' => 50000]);
        $this->assertSame(1, CashMovement::where('category', 'refund')->count());
    }

    public function test_patient_list_shows_balance(): void
    {
        [$clinic, $admin] = $this->makeClinic();
        $p = $this->makePatient($clinic);
        $this->invoice($clinic, $p, $admin, 80000);
        $this->actingAs($admin)->get(route('app.patients.index'))->assertOk()->assertSee('Jami qarzdorlik');
        $this->get(route('app.patients.index', ['balance' => 'debt']))->assertOk()->assertSee('Karimov');
    }
}
