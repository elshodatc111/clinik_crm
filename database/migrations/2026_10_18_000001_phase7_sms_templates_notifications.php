<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_logs', function (Blueprint $t) {
            $t->string('event', 30)->nullable()->after('status');
        });

        // Ilova ichidagi bildirishnomalar (har bir qabul qiluvchi uchun alohida qator)
        Schema::create('app_notifications', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('clinic_id')->nullable()->index();
            $t->unsignedBigInteger('user_id')->index();
            $t->string('type', 20)->default('info');     // info | system | diagnostic | sms | booking | lab | billing
            $t->string('level', 10)->default('info');    // info | success | warning | danger
            $t->string('title', 160);
            $t->text('body')->nullable();
            $t->string('url', 255)->nullable();
            $t->string('key', 80)->nullable();           // takroriy ogohlantirishni oldini olish
            $t->string('batch', 40)->nullable();         // superadmin yuborgan xabar guruhi
            $t->unsignedBigInteger('sender_id')->nullable();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'read_at']);
            $t->index(['batch']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
        Schema::table('sms_logs', fn (Blueprint $t) => $t->dropColumn('event'));
    }
};
