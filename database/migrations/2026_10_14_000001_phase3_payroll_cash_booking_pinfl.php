<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---- Ish haqi sozlamalari (xodim bo'yicha) ----
        Schema::table('users', function (Blueprint $t) {
            $t->string('pay_type', 20)->default('none');           // none | monthly | daily | percent | mixed
            $t->unsignedBigInteger('pay_base')->default(0);        // oylik maosh yoki kunlik stavka (so'm)
            $t->decimal('pay_percent', 5, 2)->default(0);          // foiz
            $t->string('pay_scope', 10)->default('own');           // own = o'z hisob-fakturalaridan, clinic = butun klinika tushumidan
            $t->boolean('online_enabled')->default(false);         // onlayn qabulda ko'rinadimi
        });
        Schema::table('services', function (Blueprint $t) {
            $t->boolean('online_enabled')->default(false);
        });
        Schema::table('clinics', function (Blueprint $t) {
            $t->string('slug', 60)->nullable()->unique();
        });
        Schema::table('appointments', function (Blueprint $t) {
            $t->string('source', 20)->nullable();                  // online
        });
        Schema::table('payme_transactions', function (Blueprint $t) {
            $t->string('order_type', 20)->default('topup')->index(); // topup | booking
        });

        Schema::create('doctor_service', function (Blueprint $t) {
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('service_id');
            $t->primary(['user_id', 'service_id']);
        });

        Schema::create('payrolls', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('user_id')->index();
            $t->string('period', 7);                                // 2026-10
            $t->string('pay_type', 20);
            $t->unsignedBigInteger('base')->default(0);
            $t->decimal('percent', 5, 2)->default(0);
            $t->unsignedSmallInteger('plan_days')->default(0);
            $t->unsignedSmallInteger('work_days')->default(0);
            $t->bigInteger('base_amount')->default(0);
            $t->bigInteger('percent_amount')->default(0);
            $t->bigInteger('bonus')->default(0);
            $t->bigInteger('penalty')->default(0);
            $t->bigInteger('advance')->default(0);
            $t->bigInteger('net')->default(0);
            $t->string('status', 10)->default('draft');            // draft | paid
            $t->timestamp('paid_at')->nullable();
            $t->unsignedBigInteger('paid_by')->nullable();
            $t->timestamps();
            $t->unique(['clinic_id', 'user_id', 'period']);
        });

        Schema::create('payroll_adjustments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('user_id')->index();
            $t->string('period', 7)->index();
            $t->string('kind', 10);                                 // bonus | penalty | advance
            $t->unsignedBigInteger('amount');
            $t->string('note')->nullable();
            $t->unsignedBigInteger('cash_movement_id')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
        });

        Schema::create('cash_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $t->date('date')->index();
            $t->string('direction', 3);                             // in | out
            $t->string('category', 20);                             // expense | salary | advance | other
            $t->string('method', 10)->default('cash');              // cash | card | transfer
            $t->unsignedBigInteger('amount');
            $t->string('note')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();          // kimga (maosh bo'lsa)
            $t->unsignedBigInteger('payroll_id')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
        });

        Schema::create('cash_closings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $t->date('date');
            $t->bigInteger('opening_cash')->default(0);
            $t->bigInteger('cash_in')->default(0);
            $t->bigInteger('non_cash_in')->default(0);
            $t->bigInteger('cash_out')->default(0);
            $t->bigInteger('expected_cash')->default(0);
            $t->bigInteger('counted_cash')->default(0);
            $t->bigInteger('difference')->default(0);
            $t->string('note')->nullable();
            $t->unsignedBigInteger('closed_by')->nullable();
            $t->timestamp('closed_at')->nullable();
            $t->timestamps();
            $t->unique(['clinic_id', 'date']);
        });

        Schema::create('online_bookings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $t->string('token', 40)->unique();
            $t->unsignedBigInteger('service_id');
            $t->unsignedBigInteger('doctor_id');
            $t->dateTime('starts_at');
            $t->dateTime('ends_at');
            $t->unsignedBigInteger('amount');                       // so'm
            $t->string('last_name');
            $t->string('first_name');
            $t->string('middle_name')->nullable();
            $t->date('birth_date')->nullable();
            $t->string('phone', 20);
            $t->string('pinfl', 14)->nullable();
            $t->string('status', 15)->default('pending');           // pending | confirmed | expired | refunded
            $t->timestamp('hold_expires_at')->nullable();
            $t->unsignedBigInteger('patient_id')->nullable();
            $t->unsignedBigInteger('appointment_id')->nullable();
            $t->unsignedBigInteger('invoice_id')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->string('ip', 45)->nullable();
            $t->string('locale', 5)->default('uz');
            $t->timestamps();
            $t->index(['doctor_id', 'starts_at']);
            $t->index(['clinic_id', 'status']);
        });

        Schema::create('pinfl_lookups', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('pinfl_masked', 20);
            $t->string('status', 10);                               // ok | not_found | error
            $t->string('note')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['pinfl_lookups', 'online_bookings', 'cash_closings', 'cash_movements', 'payroll_adjustments', 'payrolls', 'doctor_service'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('payme_transactions', fn (Blueprint $t) => $t->dropColumn('order_type'));
        Schema::table('appointments', fn (Blueprint $t) => $t->dropColumn('source'));
        Schema::table('clinics', fn (Blueprint $t) => $t->dropColumn('slug'));
        Schema::table('services', fn (Blueprint $t) => $t->dropColumn('online_enabled'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['pay_type', 'pay_base', 'pay_percent', 'pay_scope', 'online_enabled']));
    }
};
