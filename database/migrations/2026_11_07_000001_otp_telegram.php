<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Telefonni Telegram bot orqali tasdiqlash (ro'yxatdan o'tish va parolni tiklash). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_codes', function (Blueprint $t) {
            $t->string('purpose', 20)->default('register')->after('phone');
            $t->string('channel', 10)->default('sms')->after('purpose');
        });

        Schema::create('otp_telegram_requests', function (Blueprint $t) {
            $t->id();
            $t->string('phone', 20)->index();
            $t->string('purpose', 20); // register | reset
            $t->string('token_hash', 64)->unique();
            $t->unsignedBigInteger('chat_id')->nullable()->index();
            $t->string('state', 20)->default('pending'); // pending | awaiting_contact | sent | failed
            $t->string('note')->nullable();
            $t->string('ip', 45)->nullable();
            $t->dateTime('expires_at');
            $t->timestamps();
        });

        // Tasdiqlangan "telefon <-> Telegram chat" bog'lanishi
        Schema::create('otp_telegram_links', function (Blueprint $t) {
            $t->id();
            $t->string('phone', 20)->unique();
            $t->unsignedBigInteger('chat_id')->index();
            $t->unsignedBigInteger('tg_user_id')->nullable();
            $t->string('username', 64)->nullable();
            $t->dateTime('verified_at');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_telegram_links');
        Schema::dropIfExists('otp_telegram_requests');
        Schema::table('sms_codes', fn (Blueprint $t) => $t->dropColumn(['purpose', 'channel']));
    }
};
