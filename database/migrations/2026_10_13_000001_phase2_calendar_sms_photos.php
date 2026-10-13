<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Qon guruhi nomi ("AB(IV) Rh-") 5 belgidan uzun
        Schema::table('patients', function (Blueprint $t) {
            $t->string('blood_group', 20)->nullable()->change();
        });

        Schema::table('appointments', function (Blueprint $t) {
            $t->timestamp('remind_24h_at')->nullable();
            $t->timestamp('remind_2h_at')->nullable();
        });

        Schema::create('patient_photos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('patient_id')->index();
            $t->string('path');
            $t->string('title')->nullable();
            $t->unsignedInteger('width')->nullable();
            $t->unsignedInteger('height')->nullable();
            $t->unsignedBigInteger('original_size')->default(0);
            $t->unsignedBigInteger('size')->default(0);
            $t->unsignedBigInteger('uploaded_by')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_photos');
        Schema::table('appointments', function (Blueprint $t) {
            $t->dropColumn(['remind_24h_at', 'remind_2h_at']);
        });
    }
};
