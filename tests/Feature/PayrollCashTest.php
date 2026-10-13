<?php

namespace Tests\Feature;

use App\Models\CashClosing;
use App\Models\CashMovement;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Payroll;
use Tests\ClinicTestCase;

class PayrollCashTest extends ClinicTestCase
{
    public function test_paying_payroll_creates_cash_outflow(): void
    {
        [$clinic, $admin] = $this->makeClinic();
        $staff = \App\Models\User::create([
            'clinic_id' => $clinic->id, 'name' => 'Qabulxona', 'phone' => $this->phone(), 'password' => 'password123',
            'role' => 'receptionist', 'pay_type' => 'monthly', 'pay_base' => 2600000, 'is_active' => true,
        ]);

        $this->actingAs($admin)->get(route('app.payroll.index'))->assertOk();
        $row = Payroll::where('user_id', $staff->id)->firstOrFail();
        $this->assertGreaterThan(0, $row->net);

        $this->actingAs($admin)->post(route('app.payroll.pay', $row), ['method' => 'cash'])->assertRedirect();
        $this->assertSame('paid', $row->fresh()->status);
        $this->assertDatabaseHas('cash_movements', ['payroll_id' => $row->id, 'category' => 'salary', 'amount' => $row->net]);
    }

    public function test_cash_day_closing_blocks_new_movements(): void
    {
        [$clinic, $admin] = $this->makeClinic();
        $today = now()->toDateString();

        $this->actingAs($admin)->post(route('app.cash.store'), [
            'date' => $today, 'direction' => 'out', 'category' => 'expense', 'method' => 'cash', 'amount' => 50000, 'note' => 'Suv',
        ])->assertRedirect();
        $this->assertSame(1, CashMovement::count());

        $this->actingAs($admin)->post(route('app.cash.close'), ['date' => $today, 'counted_cash' => 0])->assertRedirect();
        $closing = CashClosing::firstOrFail();
        $this->assertSame(-50000, (int) $closing->expected_cash);
        $this->assertSame(50000, (int) $closing->difference);

        $this->actingAs($admin)->post(route('app.cash.store'), [
            'date' => $today, 'direction' => 'out', 'category' => 'expense', 'method' => 'cash', 'amount' => 1000,
        ])->assertSessionHas('error');
        $this->assertSame(1, CashMovement::count());
    }
}
