<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    const SUPER = 'super_admin';
    const ADMIN = 'clinic_admin';
    const RECEPTION = 'receptionist';
    const DOCTOR = 'doctor';
    const CASHIER = 'cashier';
    const LAB = 'laborant';

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'extra_roles' => 'array',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function hasTwoFactor(): bool
    {
        return $this->two_factor_confirmed_at !== null && $this->two_factor_secret;
    }

    public static function clinicRoles(): array
    {
        return [
            self::ADMIN => 'Admin / Direktor',
            self::RECEPTION => 'Qabulxona',
            self::DOCTOR => 'Shifokor',
            self::CASHIER => 'Kassir',
            self::LAB => 'Laborant',
        ];
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public static function clinicDoctors()
    {
        return static::query()->where('clinic_id', \App\Support\Tenant::id())
            ->where(fn ($q) => $q->where('role', self::DOCTOR)->orWhere('extra_roles', 'like', '%"doctor"%'))
            ->where('is_active', true)->orderBy('name');
    }

    public static function clinicStaff()
    {
        return static::query()->where('clinic_id', \App\Support\Tenant::id());
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->avatar_path) : null;
    }

    public function initial(): string
    {
        return mb_strtoupper(mb_substr(trim($this->name), 0, 1));
    }

    public function isSuper(): bool
    {
        return $this->role === self::SUPER;
    }

    /** Super Admin klinikaga "kirgan" paytda klinika admini sifatida ishlaydi. */
    public function effectiveRole(): string
    {
        if ($this->isSuper() && session('impersonate_clinic_id')) {
            return self::ADMIN;
        }

        return $this->role;
    }

    /** Qo'shimcha ruxsat berilgan rollar (asosiy roldan tashqari). */
    public function extraRoles(): array
    {
        $x = $this->extra_roles;

        return is_array($x) ? array_values(array_intersect($x, array_keys(self::assignableRoles()))) : [];
    }

    /** Foydalanuvchining barcha rollari: asosiy + qo'shimcha. */
    public function allRoles(): array
    {
        return array_values(array_unique(array_merge([$this->effectiveRole()], $this->isSuper() && ! session('impersonate_clinic_id') ? [] : $this->extraRoles())));
    }

    public function hasRole(string ...$roles): bool
    {
        return count(array_intersect($this->allRoles(), $roles)) > 0;
    }

    /** Qo'shimcha berilishi mumkin bo'lgan rollar (admin rolini qo'shimcha qilib bo'lmaydi). */
    public static function assignableRoles(): array
    {
        return [self::RECEPTION => 'Qabulxona', self::DOCTOR => 'Shifokor', self::CASHIER => 'Kassir', self::LAB => 'Laborant'];
    }

    /** Direktor: admin rolidagi, lavozimi "admin" deb belgilanmagan foydalanuvchi. */
    public function isDirector(): bool
    {
        if ($this->isSuper()) {
            return (bool) session('impersonate_clinic_id');
        }

        return $this->role === self::ADMIN && $this->position !== 'admin';
    }

    /** Faqat o'z bemorlarini ko'radigan shifokor (admin emas). */
    public function isScopedDoctor(): bool
    {
        return $this->hasRole(self::DOCTOR) && ! $this->hasRole(self::ADMIN);
    }

    public function positionLabel(): ?string
    {
        if ($this->role !== self::ADMIN) {
            return null;
        }

        return $this->position === 'admin' ? __('Admin') : __('Direktor');
    }

    public function roleLabel(): string
    {
        if ($this->role === self::ADMIN) {
            return $this->positionLabel();
        }

        return __(self::clinicRoles()[$this->role] ?? ($this->isSuper() ? 'Super Admin' : $this->role));
    }

    /** Barcha rollar nomlari (belgi sifatida ko'rsatish uchun). */
    public function roleLabels(): array
    {
        $out = [$this->roleLabel()];
        foreach ($this->extraRoles() as $r) {
            $out[] = __(self::assignableRoles()[$r]);
        }

        return $out;
    }
}
