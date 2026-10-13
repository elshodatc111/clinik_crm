<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('clinic_id')->nullable()->index();
            $t->string('name');
            $t->string('email')->nullable()->unique();
            $t->string('phone', 20)->nullable()->unique();
            $t->string('password');
            $t->string('role', 30)->default('clinic_admin')->index();
            $t->boolean('is_active')->default(true);
            $t->string('locale', 5)->default('uz');
            $t->string('specialty')->nullable();
            $t->string('room')->nullable();
            $t->unsignedTinyInteger('percent')->default(0);
            $t->timestamp('phone_verified_at')->nullable();
            $t->timestamp('last_login_at')->nullable();
            $t->rememberToken();
            $t->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $t) {
            $t->string('email')->primary();
            $t->string('token');
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->foreignId('user_id')->nullable()->index();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->longText('payload');
            $t->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
