<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Avans (oldindan to'lov): hisob-fakturasiz to'lov — invoice_id bo'sh, patient_id to'ldiriladi
        Schema::table('payments', function (Blueprint $t) {
            $t->unsignedBigInteger('invoice_id')->nullable()->change();
            $t->unsignedBigInteger('patient_id')->nullable()->index();
        });

        // Avans sarfi: hisob-fakturani avansdan yopish yoki bemorga qaytarish
        Schema::create('credit_applications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('patient_id')->index();
            $t->unsignedBigInteger('invoice_id')->nullable()->index();
            $t->string('kind', 10);                              // invoice | refund
            $t->unsignedBigInteger('amount');
            $t->unsignedBigInteger('cash_movement_id')->nullable();
            $t->string('note')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_applications');
        Schema::table('payments', fn (Blueprint $t) => $t->dropColumn('patient_id'));
    }
};
