<?php

namespace Tests\Feature;

use App\Services\BookingService;
use Illuminate\Support\Facades\DB;
use Tests\ClinicTestCase;

class SystemOfferTest extends ClinicTestCase
{
    public function test_super_admin_sees_system_health_and_clinic_admin_cannot(): void
    {
        [$clinic, $admin] = $this->makeClinic();
        $this->actingAs($admin)->get(route('admin.system.index'))->assertForbidden();
        $this->actingAs($this->superAdmin())->get(route('admin.system.index'))->assertOk()->assertSee('Tizim holati');
    }

    public function test_requests_are_recorded_in_metrics(): void
    {
        [$clinic, $admin] = $this->makeClinic();
        $this->actingAs($admin)->get(route('app.dashboard'))->assertOk();
        $this->assertGreaterThan(0, (int) DB::table('request_metrics')->sum('hits'));
    }

    public function test_booking_offer_page_uses_custom_or_default_text(): void
    {
        [$clinic, $admin] = $this->makeClinic();
        $clinic->update(['slug' => 'test-klinika']);
        $this->get(route('booking.offer', 'test-klinika'))->assertOk()->assertSee($clinic->name);

        $clinic->update(['settings' => ['booking_offer' => 'Maxsus shartlar matni']]);
        $this->assertSame('Maxsus shartlar matni', BookingService::offerText($clinic->fresh()));
        $this->get(route('booking.offer', 'test-klinika'))->assertSee('Maxsus shartlar matni');
    }
}
