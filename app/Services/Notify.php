<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Support\Str;

/** Ilova ichidagi bildirishnomalar. Qabul qiluvchilar tenant scope'idan mustaqil topiladi. */
class Notify
{
    /** Klinika admini va direktorlari (clinic_admin roli). */
    public static function admins(int $clinicId): array
    {
        return User::withoutGlobalScopes()->where('clinic_id', $clinicId)->where('role', 'clinic_admin')->where('is_active', true)->pluck('id')->all();
    }

    /** Barcha faol Super Adminlar. */
    public static function superAdmins(): array
    {
        return User::withoutGlobalScopes()->where('role', 'super_admin')->where('is_active', true)->pluck('id')->all();
    }

    /** Berilgan rollarga ega xodimlar (asosiy yoki qo'shimcha rol) + admin/direktorlar. */
    public static function roles(int $clinicId, array $roles, bool $withAdmins = true): array
    {
        $q = User::withoutGlobalScopes()->where('clinic_id', $clinicId)->where('is_active', true)->where(function ($w) use ($roles, $withAdmins) {
            foreach ($roles as $r) {
                $w->orWhere('role', $r)->orWhere('extra_roles', 'like', '%"'.$r.'"%');
            }
            if ($withAdmins) {
                $w->orWhere('role', 'clinic_admin');
            }
        });

        return $q->pluck('id')->all();
    }

    /**
     * @param int[] $userIds
     * @param string|null $key berilsa: shu kalitli o'qilmagan xabar bo'lsa yoki 3 kun ichida yuborilgan bo'lsa qayta yuborilmaydi
     */
    public static function send(array $userIds, string $title, ?string $body = null, string $level = 'info', ?string $url = null, string $type = 'info', ?int $clinicId = null, ?string $key = null, ?string $batch = null, ?int $senderId = null): int
    {
        $n = 0;
        $now = now();
        foreach (array_unique($userIds) as $uid) {
            if ($key && AppNotification::where('user_id', $uid)->where('key', $key)->where(fn ($q) => $q->whereNull('read_at')->orWhere('created_at', '>=', now()->subDays(3)))->exists()) {
                continue;
            }
            AppNotification::create([
                'clinic_id' => $clinicId, 'user_id' => $uid, 'type' => $type, 'level' => $level, 'title' => mb_substr($title, 0, 160),
                'body' => $body, 'url' => $url, 'key' => $key, 'batch' => $batch, 'sender_id' => $senderId,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $n++;
        }

        return $n;
    }

    public static function toAdmins(int $clinicId, string $title, ?string $body = null, string $level = 'info', ?string $url = null, string $type = 'info', ?string $key = null): int
    {
        return static::send(static::admins($clinicId), $title, $body, $level, $url, $type, $clinicId, $key);
    }

    public static function newBatch(): string
    {
        return (string) Str::uuid();
    }
}
