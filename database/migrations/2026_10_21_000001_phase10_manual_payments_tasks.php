<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kartaga qo'lda to'lov (obuna uchun): chek yuboriladi, Super Admin tasdiqlaydi yoki sabab bilan bekor qiladi
        Schema::create('manual_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('amount');              // klinika ko'rsatgan summa (so'm)
            $t->unsignedBigInteger('approved_amount')->nullable(); // Super Admin tasdiqlagan summa
            $t->date('paid_at');
            $t->string('payer_card', 8)->nullable();       // to'lovchi kartasining oxirgi 4 raqami
            $t->string('note', 300)->nullable();
            $t->string('card_number', 20)->nullable();     // to'lov paytida ko'rsatilgan karta (arxiv uchun)
            $t->string('receipt_path');
            $t->string('receipt_hash', 40)->index();
            $t->string('status', 12)->default('pending')->index(); // pending | approved | rejected | canceled
            $t->string('reject_reason', 500)->nullable();
            $t->unsignedBigInteger('reviewed_by')->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->unsignedBigInteger('balance_tx_id')->nullable();
            $t->timestamps();
            $t->index(['clinic_id', 'status']);
        });

        // Fon jarayonlari (scheduler) oxirgi ishga tushish vaqti
        Schema::create('task_heartbeats', function (Blueprint $t) {
            $t->string('name', 60)->primary();
            $t->timestamp('last_run_at')->nullable();
            $t->string('last_status', 10)->default('ok');  // ok | failed
            $t->text('last_message')->nullable();
            $t->unsignedInteger('last_ms')->default(0);
            $t->unsignedBigInteger('runs')->default(0);
            $t->unsignedBigInteger('fails')->default(0);
            $t->timestamp('last_fail_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_heartbeats');
        Schema::dropIfExists('manual_payments');
    }
};
