<?php

namespace App\Support;

class Tenant
{
    /** Joriy klinika ID (xodim klinikasi yoki Super Admin "kirish" rejimidagi klinika). */
    public static function id(): ?int
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }
        if ($user->role === 'super_admin') {
            $id = session('impersonate_clinic_id');

            return $id ? (int) $id : null;
        }

        return $user->clinic_id ? (int) $user->clinic_id : null;
    }

    public static function money(int|float|null $v): string
    {
        return number_format((float) $v, 0, '.', ' ').' '.__("so'm");
    }
}
