<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $t->string('pinfl', 14)->nullable();
            $t->string('last_name');
            $t->string('first_name');
            $t->string('middle_name')->nullable();
            $t->date('birth_date')->nullable();
            $t->string('gender', 1)->nullable();
            $t->string('phone', 20)->nullable()->index();
            $t->string('phone2', 20)->nullable();
            $t->string('address')->nullable();
            $t->string('blood_group', 5)->nullable();
            $t->text('allergies')->nullable();
            $t->text('chronic_diseases')->nullable();
            $t->string('source')->nullable();
            $t->text('notes')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['clinic_id', 'pinfl']);
            $t->index(['clinic_id', 'last_name', 'first_name']);
        });

        Schema::create('services', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('group')->nullable();
            $t->unsignedBigInteger('price')->default(0);
            $t->unsignedSmallInteger('duration_min')->default(30);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('appointments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('patient_id')->index();
            $t->unsignedBigInteger('doctor_id')->nullable()->index();
            $t->unsignedBigInteger('service_id')->nullable();
            $t->dateTime('starts_at');
            $t->dateTime('ends_at');
            $t->string('status', 20)->default('scheduled');
            $t->text('comment')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->index(['clinic_id', 'starts_at']);
        });

        Schema::create('visits', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('appointment_id')->nullable()->index();
            $t->unsignedBigInteger('patient_id')->index();
            $t->unsignedBigInteger('doctor_id')->nullable()->index();
            $t->text('complaint')->nullable();
            $t->text('anamnesis')->nullable();
            $t->text('examination')->nullable();
            $t->string('diagnosis_code', 20)->nullable();
            $t->text('diagnosis_text')->nullable();
            $t->text('recommendations')->nullable();
            $t->dateTime('visited_at');
            $t->timestamps();
        });

        Schema::create('prescriptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('visit_id')->index();
            $t->string('drug');
            $t->string('dose')->nullable();
            $t->string('frequency')->nullable();
            $t->string('duration')->nullable();
            $t->timestamps();
        });

        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('patient_id')->index();
            $t->unsignedBigInteger('visit_id')->nullable();
            $t->unsignedBigInteger('doctor_id')->nullable();
            $t->string('number', 30);
            $t->unsignedBigInteger('total')->default(0);
            $t->unsignedBigInteger('discount')->default(0);
            $t->unsignedBigInteger('paid')->default(0);
            $t->string('status', 20)->default('unpaid');
            $t->text('notes')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->unique(['clinic_id', 'number']);
        });

        Schema::create('invoice_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('invoice_id')->index();
            $t->unsignedBigInteger('service_id')->nullable();
            $t->string('name');
            $t->unsignedInteger('qty')->default(1);
            $t->unsignedBigInteger('price')->default(0);
            $t->timestamps();
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('invoice_id')->index();
            $t->unsignedBigInteger('amount');
            $t->string('method', 20);
            $t->unsignedBigInteger('cashier_id')->nullable();
            $t->dateTime('paid_at');
            $t->string('note')->nullable();
            $t->timestamps();
        });

        Schema::create('lab_orders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('patient_id')->index();
            $t->unsignedBigInteger('doctor_id')->nullable();
            $t->string('test_name');
            $t->string('status', 20)->default('ordered');
            $t->text('result_value')->nullable();
            $t->string('norm')->nullable();
            $t->text('result_note')->nullable();
            $t->string('file_path')->nullable();
            $t->dateTime('ordered_at');
            $t->dateTime('completed_at')->nullable();
            $t->unsignedBigInteger('completed_by')->nullable();
            $t->unsignedBigInteger('ordered_by')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['lab_orders', 'payments', 'invoice_items', 'invoices', 'prescriptions', 'visits', 'appointments', 'services', 'patients'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
