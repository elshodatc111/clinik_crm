<?php

namespace Database\Seeders;

use App\Models\Offer;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'app_name' => 'Klinika CRM',
            'primary_color' => '#1d4ed8',
            'setup_fee' => '1000000',
            'demo_days' => '14',
            'demo_grace_days' => '0',
        ];
        foreach ($defaults as $k => $v) {
            if (Setting::get($k) === null) {
                Setting::set($k, $v);
            }
        }

        if (Plan::count() === 0) {
            // Narxlar NAMUNA — Super Admin panelida o'zgartiring
            foreach ([
                ['1-tarif', 1, 500, 200000],
                ['2-tarif', 501, 3000, 400000],
                ['3-tarif', 3001, 10000, 700000],
                ['4-tarif', 10001, null, 1200000],
            ] as $i => [$name, $min, $max, $price]) {
                Plan::create(['name' => $name, 'min_patients' => $min, 'max_patients' => $max, 'monthly_price' => $price, 'sort' => $i + 1]);
            }
        }

        if (Offer::count() === 0) {
            Offer::create([
                'version' => 1,
                'title' => 'Ommaviy oferta',
                'is_active' => true,
                'body' => file_get_contents(database_path('seeders/offer_uz.txt')),
                'body_ru' => file_get_contents(database_path('seeders/offer_ru.txt')),
            ]);
        }

        $email = env('SUPERADMIN_EMAIL', 'admin@klinika.local');
        if (! User::where('email', $email)->exists()) {
            User::create([
                'name' => 'Super Admin',
                'email' => $email,
                'password' => env('SUPERADMIN_PASSWORD', 'ChangeMe123!'),
                'role' => User::SUPER,
                'phone_verified_at' => now(),
            ]);
        }
    }
}
