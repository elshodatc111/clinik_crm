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

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public static function clinicRoles(): array
    {
        return [
            self::ADMIN => 'Klinika admini (direktor)',
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
            ->where('role', self::DOCTOR)->where('is_active', true)->orderBy('name');
    }

    public static function clinicStaff()
    {
        return static::query()->where('clinic_id', \App\Support\Tenant::id());
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

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->effectiveRole(), $roles, true);
    }

    public function roleLabel(): string
    {
        return __(self::clinicRoles()[$this->role] ?? ($this->isSuper() ? 'Super Admin' : $this->role));
    }
}
