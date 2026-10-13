<?php

namespace App\Support;

use App\Models\User;

/** Rollar bo'yicha foydalanish yo'riqnomalari (resources/data/guides.json). */
class Guides
{
    protected static ?array $cache = null;

    public static function all(): array
    {
        return self::$cache ??= json_decode(file_get_contents(resource_path('data/guides.json')), true) ?: [];
    }

    /** Foydalanuvchi ko'ra oladigan yo'riqnomalar. Admin/direktor — hammasini. */
    public static function forUser(User $u): array
    {
        $out = [];
        foreach (self::all() as $key => $g) {
            if ($u->hasRole(User::ADMIN) || $u->hasRole(...$g['roles'])) {
                $out[$key] = $g;
            }
        }

        return $out;
    }

    public static function text(array $node, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return $node[$locale] ?? $node['uz'] ?? '';
    }
}
