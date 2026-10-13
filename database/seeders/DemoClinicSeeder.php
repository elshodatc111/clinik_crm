<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Prescription;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * To'liq test (demo) klinika: xodimlar, xizmatlar, 90 bemor, qabullar, tashriflar,
 * hisob-fakturalar, to'lovlar va tahlillar (oxirgi 60 kun).
 *
 *   php artisan db:seed --class=DemoClinicSeeder
 * Qayta ishga tushirsangiz, eski demo klinika o'chirilib, qaytadan yaratiladi.
 */
class DemoClinicSeeder extends Seeder
{
    const NAME = 'Demo Klinika';

    public function run(): void
    {
        mt_srand(2026);
        $this->cleanup();

        $plan = Plan::orderBy('min_patients')->first();
        $clinic = Clinic::create([
            'name' => self::NAME, 'owner_name' => 'Aziz Karimov', 'phone' => '998901112233',
            'email' => 'demo@klinika.local', 'address' => "Toshkent sh., Chilonzor tumani, Bunyodkor ko'chasi 12",
            'status' => Clinic::ACTIVE, 'plan_id' => $plan?->id, 'balance' => 1500000,
            'setup_paid_at' => now()->subDays(20), 'paid_until' => now()->addDays(10),
            'slug' => 'demo-klinika',
            'settings' => ['working_hours' => '09:00 - 18:00', 'online_enabled' => true, 'online_start' => '09:00', 'online_end' => '18:00', 'online_days' => [1, 2, 3, 4, 5, 6], 'online_days_ahead' => 30],
        ]);
        $cid = $clinic->id;

        // ---------- Xodimlar ----------
        $mk = fn ($name, $phone, $role, $extra = []) => User::create([
            'clinic_id' => $cid, 'name' => $name, 'phone' => $phone, 'role' => $role,
            'email' => $extra['email'] ?? null, 'password' => 'demo12345', 'is_active' => true,
            'phone_verified_at' => now(),
        ] + $extra);
        $mk('Aziz Karimov', '998901112233', User::ADMIN, ['email' => 'demo@klinika.local', 'position' => 'director']);
        $mk('Malika Rahimova', '998901112211', User::ADMIN, ['position' => 'admin']);
        $mk('Madina Yusupova', '998901112244', User::RECEPTION, ['pay_type' => 'monthly', 'pay_base' => 3000000]);
        $mk('Sherzod Aliyev', '998901112255', User::CASHIER, ['pay_type' => 'daily', 'pay_base' => 120000, 'extra_roles' => ['receptionist']]);
        $mk('Nilufar Rahimova', '998901112266', User::LAB, ['pay_type' => 'mixed', 'pay_base' => 2000000, 'pay_percent' => 5, 'pay_scope' => 'clinic']);
        $doctors = [
            $mk('Dr. Bobur Tursunov', '998901112277', User::DOCTOR, ['specialty' => 'Terapevt', 'room' => '101', 'percent' => 30, 'pay_type' => 'percent', 'pay_percent' => 30, 'pay_scope' => 'own', 'online_enabled' => true]),
            $mk('Dr. Dilnoza Saidova', '998901112288', User::DOCTOR, ['specialty' => 'Kardiolog', 'room' => '102', 'percent' => 35, 'pay_type' => 'percent', 'pay_percent' => 35, 'pay_scope' => 'own', 'online_enabled' => true]),
            $mk('Dr. Jasur Ergashev', '998901112299', User::DOCTOR, ['specialty' => 'Nevrolog', 'room' => '201', 'percent' => 30, 'pay_type' => 'percent', 'pay_percent' => 30, 'pay_scope' => 'own', 'online_enabled' => true]),
            $mk('Dr. Gulnora Mirzayeva', '998901112200', User::DOCTOR, ['specialty' => 'LOR', 'room' => '202', 'percent' => 30, 'pay_type' => 'percent', 'pay_percent' => 30, 'pay_scope' => 'own', 'online_enabled' => true]),
        ];

        // ---------- Xizmatlar ----------
        $services = [];
        foreach ([
            ['Terapevt qabuli', 'Konsultatsiya', 120000, 30], ['Kardiolog qabuli', 'Konsultatsiya', 180000, 30],
            ['Nevrolog qabuli', 'Konsultatsiya', 170000, 30], ['LOR qabuli', 'Konsultatsiya', 150000, 30],
            ['EKG', 'Diagnostika', 90000, 20], ['Exokardiografiya', 'Diagnostika', 250000, 40],
            ['UZI (qorin bo\'shlig\'i)', 'Diagnostika', 160000, 30], ['Umumiy qon tahlili', 'Laboratoriya', 45000, 10],
            ['Biokimyoviy qon tahlili', 'Laboratoriya', 120000, 10], ['Umumiy siydik tahlili', 'Laboratoriya', 35000, 10],
            ['Qand miqdori', 'Laboratoriya', 30000, 10], ['Inyeksiya (ukol)', 'Muolaja', 20000, 15],
            ['Tomchilatib dori yuborish', 'Muolaja', 80000, 60], ['Massaj', 'Fizioterapiya', 100000, 40],
        ] as [$n, $g, $p, $d]) {
            $services[] = Service::create(['clinic_id' => $cid, 'name' => $n, 'group' => $g, 'price' => $p, 'duration_min' => $d, 'is_active' => true, 'online_enabled' => true]);
        }
        $consults = array_slice($services, 0, 4);

        // ---------- Bemorlar ----------
        $male = ['Akmal', 'Bekzod', 'Dilshod', 'Eldor', 'Farrux', 'Husan', 'Islom', 'Jahongir', 'Kamol', 'Lochinbek', 'Mansur', 'Nodir', 'Otabek', 'Rustam', 'Sardor', 'Timur', 'Ulugbek', 'Zafar'];
        $female = ['Aziza', 'Barno', 'Dilfuza', 'Feruza', 'Gulbahor', 'Hilola', 'Iroda', 'Kamola', 'Laylo', 'Malika', 'Nargiza', 'Olima', 'Sevara', 'Shahnoza', 'Umida', 'Zarina', 'Zilola'];
        $last = ['Karimov', 'Yusupov', 'Abdullayev', 'Rahimov', 'Tursunov', 'Saidov', 'Ergashev', 'Mirzayev', 'Aliyev', 'Hasanov', 'Ismoilov', 'Normatov', 'Qodirov', 'Sobirov', 'Xolmatov', 'Umarov'];
        $source = ['Instagram', 'Telegram', "Do'st-tanish tavsiyasi", 'Reklama (banner)', "O'tib ketayotib", 'Google', null];
        $blood = ['I+', 'I-', 'II+', 'II-', 'III+', 'III-', 'IV+', 'IV-', null];
        $allergy = [null, null, null, null, 'Penitsillin', 'Chang-to\'zon', 'Aspirin'];
        $chronic = [null, null, null, 'Gipertoniya', 'Qandli diabet', 'Gastrit', 'Bronxial astma'];
        $patients = [];
        $usedPinfl = [];
        for ($i = 0; $i < 90; $i++) {
            $isMale = mt_rand(0, 1) === 1;
            $ln = $last[array_rand($last)];
            $fn = $isMale ? $male[array_rand($male)] : $female[array_rand($female)];
            $ln = $isMale ? $ln : preg_replace('/ov$/', 'ova', preg_replace('/ev$/', 'eva', $ln));
            $birth = now()->subYears(mt_rand(1, 78))->subDays(mt_rand(0, 360))->startOfDay();
            do {
                $pinfl = ($isMale ? '3' : '4').str_pad((string) mt_rand(0, 9999999999999), 13, '0', STR_PAD_LEFT);
            } while (isset($usedPinfl[$pinfl]));
            $usedPinfl[$pinfl] = 1;
            $created = now()->subDays(mt_rand(0, 60))->setTime(mt_rand(9, 17), mt_rand(0, 59));
            $patients[] = Patient::create([
                'clinic_id' => $cid, 'pinfl' => $pinfl, 'last_name' => $ln, 'first_name' => $fn,
                'middle_name' => ($isMale ? $male[array_rand($male)].' o\'g\'li' : $male[array_rand($male)].' qizi'),
                'birth_date' => $birth->toDateString(), 'gender' => $isMale ? 'm' : 'f',
                'phone' => '99890'.mt_rand(1000000, 9999999), 'address' => 'Toshkent sh., '.mt_rand(1, 60).'-uy',
                'blood_group' => $blood[array_rand($blood)], 'allergies' => $allergy[array_rand($allergy)],
                'chronic_diseases' => $chronic[array_rand($chronic)], 'source' => $source[array_rand($source)],
                'created_at' => $created, 'updated_at' => $created,
            ]);
        }

        // ---------- Qabullar, tashriflar, hisob-fakturalar, to'lovlar ----------
        $complaints = ['Bosh og\'rig\'i va holsizlik', 'Yo\'tal, tomoq og\'rig\'i', 'Ko\'krak qafasida og\'riq', 'Qon bosimi oshishi', 'Bel og\'rig\'i', 'Bosh aylanishi', 'Burun bitishi', 'Qorin og\'rig\'i', 'Uyqusizlik', 'Nafas qisishi'];
        $diag = [['J06.9', 'O\'tkir respirator infeksiya'], ['I10', 'Essensial gipertenziya'], ['M54.5', 'Bel og\'rig\'i'], ['K29.7', 'Gastrit'], ['G44.2', 'Taranglik bosh og\'rig\'i'], ['J30.4', 'Allergik rinit'], ['R51', 'Bosh og\'rig\'i']];
        $drugs = [['Paratsetamol', '500 mg', '3 mahal', '5 kun'], ['Ibuprofen', '400 mg', '2 mahal', '5 kun'], ['Omeprazol', '20 mg', '1 mahal', '14 kun'], ['Amlodipin', '5 mg', '1 mahal', '30 kun'], ['Vitamin D3', '2000 IU', '1 mahal', '30 kun']];
        $methods = ['cash', 'cash', 'cash', 'card', 'card', 'payme', 'click', 'transfer'];
        $seq = [];
        $apptCount = 0;
        for ($day = -60; $day <= 7; $day++) {
            $date = now()->addDays($day)->startOfDay();
            if ($date->isSunday()) {
                continue;
            }
            $n = mt_rand(3, 9);
            for ($k = 0; $k < $n; $k++) {
                $doc = $doctors[array_rand($doctors)];
                $svc = $consults[array_search($doc->specialty, ['Terapevt', 'Kardiolog', 'Nevrolog', 'LOR'])];
                $patient = $patients[array_rand($patients)];
                $start = $date->copy()->setTime(9 + intdiv($k * 40, 60) + mt_rand(0, 1), [0, 20, 40][$k % 3]);
                $end = $start->copy()->addMinutes($svc->duration_min);
                if ($day > 0 || ($day === 0 && $start->isFuture())) {
                    $status = 'scheduled';
                } elseif ($day === 0) {
                    $status = ['arrived', 'in_progress', 'done'][mt_rand(0, 2)];
                } else {
                    $r = mt_rand(1, 100);
                    $status = $r <= 80 ? 'done' : ($r <= 90 ? 'no_show' : 'canceled');
                }
                $app = Appointment::create([
                    'clinic_id' => $cid, 'patient_id' => $patient->id, 'doctor_id' => $doc->id, 'service_id' => $svc->id,
                    'starts_at' => $start, 'ends_at' => $end, 'status' => $status,
                    'created_at' => $start->copy()->subDays(1), 'updated_at' => $start,
                ]);
                $apptCount++;
                if ($status !== 'done') {
                    continue;
                }
                $d = $diag[array_rand($diag)];
                $visit = Visit::create([
                    'clinic_id' => $cid, 'appointment_id' => $app->id, 'patient_id' => $patient->id, 'doctor_id' => $doc->id,
                    'complaint' => $complaints[array_rand($complaints)], 'anamnesis' => '3 kundan beri bezovta qiladi.',
                    'examination' => 'Umumiy holat qoniqarli. AB 130/85, puls 76.', 'diagnosis_code' => $d[0], 'diagnosis_text' => $d[1],
                    'recommendations' => 'Ko\'proq suyuqlik iching, 7 kundan so\'ng nazoratga keling.',
                    'visited_at' => $start, 'created_at' => $start, 'updated_at' => $start,
                ]);
                if (mt_rand(0, 1)) {
                    $dr = $drugs[array_rand($drugs)];
                    Prescription::create(['clinic_id' => $cid, 'visit_id' => $visit->id, 'drug' => $dr[0], 'dose' => $dr[1], 'frequency' => $dr[2], 'duration' => $dr[3]]);
                }

                // Hisob-faktura: konsultatsiya + ba'zan qo'shimcha xizmat
                $items = [[$svc, 1]];
                if (mt_rand(1, 100) <= 55) {
                    $items[] = [$services[mt_rand(4, 13)], 1];
                }
                $total = array_sum(array_map(fn ($it) => $it[0]->price * $it[1], $items));
                $discount = mt_rand(1, 100) <= 15 ? (int) round($total * 0.1 / 1000) * 1000 : 0;
                $net = $total - $discount;
                $r = mt_rand(1, 100);
                $paid = $r <= 72 ? $net : ($r <= 88 ? (int) (round($net * 0.5 / 1000) * 1000) : 0);
                $key = $start->format('ymd');
                $seq[$key] = ($seq[$key] ?? 0) + 1;
                $inv = Invoice::create([
                    'clinic_id' => $cid, 'patient_id' => $patient->id, 'visit_id' => $visit->id, 'doctor_id' => $doc->id,
                    'number' => 'INV-'.$key.'-'.str_pad((string) $seq[$key], 3, '0', STR_PAD_LEFT),
                    'total' => $total, 'discount' => $discount, 'paid' => $paid,
                    'status' => $paid >= $net ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
                    'created_at' => $start, 'updated_at' => $start,
                ]);
                foreach ($items as [$s, $q]) {
                    InvoiceItem::create(['clinic_id' => $cid, 'invoice_id' => $inv->id, 'service_id' => $s->id, 'name' => $s->name, 'qty' => $q, 'price' => $s->price]);
                }
                if ($paid > 0) {
                    Payment::create([
                        'clinic_id' => $cid, 'invoice_id' => $inv->id, 'amount' => $paid, 'method' => $methods[array_rand($methods)],
                        'cashier_id' => null, 'paid_at' => $end->copy()->addMinutes(mt_rand(1, 30)),
                        'created_at' => $end, 'updated_at' => $end,
                    ]);
                }

                // Ba'zan tahlil yo'llanmasi
                if (mt_rand(1, 100) <= 25) {
                    $done = $day < -1 || mt_rand(0, 1);
                    $test = [['Umumiy qon tahlili', '4.5-5.5', 'Normada'], ['Qand miqdori', '3.3-5.5 mmol/l', '5.1 mmol/l'], ['Umumiy siydik tahlili', 'Normada', 'Normada'], ['Biokimyoviy qon tahlili', 'ALT <40', 'ALT 32']][mt_rand(0, 3)];
                    LabOrder::create([
                        'clinic_id' => $cid, 'patient_id' => $patient->id, 'doctor_id' => $doc->id, 'test_name' => $test[0],
                        'status' => $done ? 'done' : 'ordered', 'result_value' => $done ? $test[2] : null, 'norm' => $test[1],
                        'ordered_at' => $start, 'completed_at' => $done ? $start->copy()->addHours(3) : null,
                        'created_at' => $start, 'updated_at' => $start,
                    ]);
                }
            }
        }

        // ---------- Kassa xarajatlari ----------
        foreach ([['Suv va choy', 60000], ['Tozalash vositalari', 140000], ['Printer qog\'ozi', 85000], ['Internet to\'lovi', 250000], ['Dori-darmon (birinchi yordam)', 190000]] as $i => [$note, $amt]) {
            DB::table('cash_movements')->insert([
                'clinic_id' => $cid, 'date' => now()->subDays($i)->toDateString(), 'direction' => 'out', 'category' => 'expense',
                'method' => 'cash', 'amount' => $amt, 'note' => $note, 'created_by' => null, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->command?->info("Demo klinika tayyor: {$apptCount} qabul, ".count($patients).' bemor.');
        $this->command?->info('Kirish: telefon 998901112233 (direktor), parol: demo12345');
        $this->command?->info('Boshqa rollar: 998901112244 (qabulxona), ...11 (admin), ...55 (kassir + qabulxona ruxsati), ...66 (laborant), ...77/88/99/00 (shifokorlar) — parol bir xil.');
    }

    protected function cleanup(): void
    {
        $old = Clinic::where('name', self::NAME)->get();
        foreach ($old as $c) {
            foreach (['lab_orders', 'payments', 'invoice_items', 'invoices', 'prescriptions', 'visits', 'appointments', 'services', 'patients', 'audit_logs', 'sms_logs', 'payrolls', 'payroll_adjustments', 'cash_movements', 'cash_closings', 'online_bookings', 'pinfl_lookups', 'credit_applications', 'patient_photos'] as $t) {
                DB::table($t)->where('clinic_id', $c->id)->delete();
            }
            DB::table('users')->where('clinic_id', $c->id)->delete();
            DB::table('balance_transactions')->where('clinic_id', $c->id)->delete();
            DB::table('clinic_status_logs')->where('clinic_id', $c->id)->delete();
            DB::table('clinics')->where('id', $c->id)->delete();
        }
        // Telefon raqamlari band bo'lib qolmasin
        DB::table('users')->whereIn('phone', ['998901112233', '998901112244', '998901112255', '998901112266', '998901112277', '998901112288', '998901112299', '998901112200', '998901112211'])->delete();
    }
}
