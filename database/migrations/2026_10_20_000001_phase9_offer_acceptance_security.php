<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $t) {
            if (! Schema::hasColumn('offers', 'body_ru')) {
                $t->longText('body_ru')->nullable()->after('body');
            }
        });
        Schema::table('offer_acceptances', function (Blueprint $t) {
            if (! Schema::hasColumn('offer_acceptances', 'user_id')) {
                $t->unsignedBigInteger('user_id')->nullable()->index()->after('clinic_id');
                $t->string('context', 20)->default('register')->after('user_id'); // register | login
            }
        });
        Schema::table('users', function (Blueprint $t) {
            if (! Schema::hasColumn('users', 'two_factor_secret')) {
                $t->text('two_factor_secret')->nullable();
                $t->text('two_factor_recovery_codes')->nullable();
                $t->timestamp('two_factor_confirmed_at')->nullable();
            }
        });
        Schema::table('online_bookings', function (Blueprint $t) {
            if (! Schema::hasColumn('online_bookings', 'privacy_accepted_at')) {
                $t->timestamp('privacy_accepted_at')->nullable();
            }
        });

        // Namuna oferta o'rniga to'liq oferta (yangi versiya) — faqat faol oferta namuna bo'lsa yoki umuman bo'lmasa
        $active = DB::table('offers')->where('is_active', true)->orderByDesc('version')->first();
        $isSample = ! $active || str_contains((string) $active->body, 'NAMUNA') || stripos((string) $active->title, 'namuna') !== false;
        $uz = database_path('seeders/offer_uz.txt');
        $ru = database_path('seeders/offer_ru.txt');
        if ($isSample && is_file($uz)) {
            DB::table('offers')->update(['is_active' => false]);
            DB::table('offers')->insert([
                'version' => (int) DB::table('offers')->max('version') + 1,
                'title' => 'Ommaviy oferta',
                'body' => file_get_contents($uz),
                'body_ru' => is_file($ru) ? file_get_contents($ru) : null,
                'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('online_bookings', fn (Blueprint $t) => $t->dropColumn('privacy_accepted_at'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']));
        Schema::table('offer_acceptances', fn (Blueprint $t) => $t->dropColumn(['user_id', 'context']));
        Schema::table('offers', fn (Blueprint $t) => $t->dropColumn('body_ru'));
    }
};
