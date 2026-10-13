<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_bookings', function (Blueprint $t) {
            $t->timestamp('offer_accepted_at')->nullable();
            $t->string('offer_hash', 40)->nullable();
        });

        // Daqiqa/5-daqiqalik paketlarga jamlangan so'rovlar statistikasi (yengil)
        Schema::create('request_metrics', function (Blueprint $t) {
            $t->id();
            $t->dateTime('bucket');
            $t->string('route', 120);
            $t->unsignedBigInteger('clinic_id')->default(0);
            $t->unsignedInteger('hits')->default(0);
            $t->unsignedInteger('errors')->default(0);
            $t->unsignedBigInteger('total_ms')->default(0);
            $t->unsignedInteger('max_ms')->default(0);
            $t->unique(['bucket', 'route', 'clinic_id']);
            $t->index('bucket');
        });

        Schema::create('system_errors', function (Blueprint $t) {
            $t->id();
            $t->string('kind', 20)->default('exception'); // exception | slow
            $t->string('route', 120)->nullable();
            $t->string('method', 8)->nullable();
            $t->unsignedSmallInteger('status')->nullable();
            $t->unsignedInteger('duration_ms')->nullable();
            $t->unsignedBigInteger('clinic_id')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('message', 500)->nullable();
            $t->string('file', 255)->nullable();
            $t->timestamps();
            $t->index(['kind', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_errors');
        Schema::dropIfExists('request_metrics');
        Schema::table('online_bookings', fn (Blueprint $t) => $t->dropColumn(['offer_accepted_at', 'offer_hash']));
    }
};
