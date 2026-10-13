<?php

namespace Tests;

use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class ClinicTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        // Xavfsizlik: RefreshDatabase haqiqiy MySQL bazani o'chirib yubormasligi uchun
        if (getenv('DB_CONNECTION') !== 'sqlite') {
            throw new \RuntimeException("Testlar faqat sqlite (:memory:) bilan ishlashi kerak. phpunit.xml ni tekshiring.");
        }
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    protected function phone(): string
    {
        return '998'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
    }

    /** @return array{0: Clinic, 1: User} */
    protected function makeClinic(array $attrs = [], string $role = 'clinic_admin'): array
    {
        $clinic = Clinic::create($attrs + [
            'name' => 'Test klinika', 'owner_name' => 'Egasi', 'phone' => $this->phone(),
            'status' => 'trial', 'trial_ends_at' => now()->addDays(14), 'settings' => [],
        ]);
        $user = User::create([
            'clinic_id' => $clinic->id, 'name' => 'Admin', 'phone' => $this->phone(),
            'password' => 'password123', 'role' => $role,
        ]);

        return [$clinic, $user];
    }

    protected function makePatient(Clinic $clinic, array $attrs = []): Patient
    {
        return Patient::create($attrs + ['clinic_id' => $clinic->id, 'last_name' => 'Karimov', 'first_name' => 'Ali']);
    }

    protected function superAdmin(): User
    {
        return User::where('role', 'super_admin')->firstOrFail();
    }
}
