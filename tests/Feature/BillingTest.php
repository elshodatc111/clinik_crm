<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Support\Facades\Storage;
use Tests\ClinicTestCase;

class BillingTest extends ClinicTestCase
{
    public function test_plan_is_chosen_by_patient_count(): void
    {
        foreach ([[1, '1-tarif'], [500, '1-tarif'], [501, '2-tarif'], [3000, '2-tarif'], [3001, '3-tarif'], [10000, '3-tarif'], [10001, '4-tarif'], [50000, '4-tarif']] as [$n, $name]) {
            $this->assertSame($name, BillingService::planFor($n)->name, "n={$n}");
        }
    }

    public function test_subscribe_needs_balance_then_activates_with_free_first_month(): void
    {
        [$c] = $this->makeClinic();
        $this->assertNotNull(BillingService::subscribe($c));

        BillingService::adjust($c, 1000000, 'topup');
        $this->assertNull(BillingService::subscribe($c->fresh()));

        $c->refresh();
        $this->assertSame('active', $c->status);
        $this->assertSame(0, (int) $c->balance);
        $this->assertTrue($c->paid_until->between(now()->addDays(29), now()->addDays(31)));
    }

    public function test_expired_demo_is_deleted_with_all_data(): void
    {
        [$c, $u] = $this->makeClinic(['trial_ends_at' => now()->subDay()]);
        $this->makePatient($c);

        BillingService::runDaily();

        $this->assertNull(Clinic::find($c->id));
        $this->assertNull(User::find($u->id));
        $this->assertSame(0, \App\Models\Patient::withoutGlobalScopes()->withTrashed()->count());
    }

    public function test_running_demo_is_kept(): void
    {
        [$c] = $this->makeClinic(['trial_ends_at' => now()->addDays(5)]);
        BillingService::runDaily();
        $this->assertNotNull(Clinic::find($c->id));
    }

    public function test_unpaid_month_goes_view_only_then_blocked_then_deleted(): void
    {
        Storage::fake('local');
        [$c, $u] = $this->makeClinic(['status' => 'active', 'paid_until' => now()->subMinute(), 'setup_paid_at' => now()->subDays(30)]);

        BillingService::runDaily();
        $this->assertSame('view_only', $c->fresh()->status);

        $c->update(['paid_until' => now()->subDays(61)]);
        BillingService::runDaily();
        $c->refresh();
        $this->assertSame('blocked', $c->status);
        $this->assertNotNull($c->blocked_at);

        $c->update(['blocked_at' => now()->subDays(31)]);
        BillingService::runDaily();
        $this->assertNull(Clinic::find($c->id));
    }

    public function test_hold_deletion_prevents_purge(): void
    {
        [$c] = $this->makeClinic(['status' => 'blocked', 'blocked_at' => now()->subDays(40), 'paid_until' => now()->subDays(100), 'hold_deletion' => true]);
        BillingService::runDaily();
        $this->assertNotNull(Clinic::find($c->id));
    }

    public function test_topup_restores_blocked_clinic(): void
    {
        [$c] = $this->makeClinic(['status' => 'blocked', 'blocked_at' => now()->subDays(5), 'paid_until' => now()->subDays(70)]);
        $c = BillingService::adjust($c, 500000, 'topup');
        BillingService::afterTopup($c);

        $c->refresh();
        $this->assertSame('active', $c->status);
        $this->assertNull($c->blocked_at);
        $this->assertTrue($c->paid_until->isFuture());
    }
}
