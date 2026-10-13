<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('position', 10)->nullable()->after('role');   // director | admin (faqat clinic_admin uchun)
            $t->text('extra_roles')->nullable()->after('position');  // JSON: qo'shimcha ruxsat berilgan rollar
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['position', 'extra_roles']));
    }
};
