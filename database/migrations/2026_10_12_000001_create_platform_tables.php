<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->timestamps();
        });

        Schema::create('plans', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->unsignedInteger('min_patients');
            $t->unsignedInteger('max_patients')->nullable();
            $t->unsignedBigInteger('monthly_price')->default(0);
            $t->unsignedTinyInteger('sort')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('clinics', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('owner_name');
            $t->string('phone', 20)->index();
            $t->string('email')->nullable();
            $t->string('address')->nullable();
            $t->string('logo_path')->nullable();
            $t->string('status', 20)->default('trial')->index();
            $t->timestamp('trial_ends_at')->nullable();
            $t->timestamp('setup_paid_at')->nullable();
            $t->timestamp('paid_until')->nullable();
            $t->timestamp('blocked_at')->nullable();
            $t->unsignedBigInteger('plan_id')->nullable();
            $t->bigInteger('balance')->default(0);
            $t->boolean('hold_deletion')->default(false);
            $t->string('last_notice_key')->nullable();
            $t->json('settings')->nullable();
            $t->timestamps();
        });

        Schema::create('clinic_status_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $t->string('from_status', 20)->nullable();
            $t->string('to_status', 20);
            $t->string('reason')->nullable();
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('offers', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('version')->unique();
            $t->string('title');
            $t->longText('body');
            $t->boolean('is_active')->default(false);
            $t->timestamps();
        });

        // Oferta roziligi dalili: klinika o'chirilgandan keyin ham saqlanadi
        Schema::create('offer_acceptances', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('clinic_id')->nullable()->index();
            $t->string('clinic_name');
            $t->string('user_name');
            $t->string('phone', 20);
            $t->unsignedBigInteger('offer_id');
            $t->unsignedInteger('offer_version');
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->timestamp('accepted_at');
        });

        Schema::create('sms_codes', function (Blueprint $t) {
            $t->id();
            $t->string('phone', 20)->index();
            $t->string('code');
            $t->timestamp('expires_at');
            $t->unsignedTinyInteger('attempts')->default(0);
            $t->timestamps();
        });

        Schema::create('sms_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('clinic_id')->nullable()->index();
            $t->string('phone', 20);
            $t->text('message');
            $t->string('status', 20)->default('pending');
            $t->text('response')->nullable();
            $t->timestamps();
        });

        Schema::create('balance_transactions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $t->string('type', 20); // topup, charge, setup_fee, manual, refund
            $t->bigInteger('amount');
            $t->bigInteger('balance_after');
            $t->string('description')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('topup_orders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('amount'); // so'm
            $t->string('status', 20)->default('new'); // new, paid, canceled
            $t->timestamps();
        });

        Schema::create('payme_transactions', function (Blueprint $t) {
            $t->id();
            $t->string('payme_id')->unique();
            $t->unsignedBigInteger('topup_order_id')->index();
            $t->unsignedBigInteger('clinic_id')->index();
            $t->unsignedBigInteger('amount'); // tiyin
            $t->tinyInteger('state'); // 1 yaratilgan, 2 bajarilgan, -1/-2 bekor
            $t->tinyInteger('reason')->nullable();
            $t->unsignedBigInteger('payme_time');
            $t->unsignedBigInteger('create_time');
            $t->unsignedBigInteger('perform_time')->default(0);
            $t->unsignedBigInteger('cancel_time')->default(0);
            $t->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('clinic_id')->nullable()->index();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('action', 20);
            $t->string('auditable_type', 50);
            $t->unsignedBigInteger('auditable_id')->nullable();
            $t->json('changes')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->timestamp('created_at')->nullable();
            $t->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        foreach (['audit_logs', 'payme_transactions', 'topup_orders', 'balance_transactions', 'sms_logs', 'sms_codes', 'offer_acceptances', 'offers', 'clinic_status_logs', 'clinics', 'plans', 'platform_settings'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
