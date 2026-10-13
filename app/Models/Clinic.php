<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Clinic extends Model
{
    use Auditable;

    const TRIAL = 'trial';
    const ACTIVE = 'active';
    const VIEW_ONLY = 'view_only';
    const BLOCKED = 'blocked';
    const SUSPENDED = 'suspended';

    protected $guarded = [];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'setup_paid_at' => 'datetime',
        'paid_until' => 'datetime',
        'blocked_at' => 'datetime',
        'hold_deletion' => 'boolean',
        'settings' => 'array',
        'balance' => 'integer',
    ];

    public static function statusLabels(): array
    {
        return [
            self::TRIAL => 'Demo',
            self::ACTIVE => 'Faol',
            self::VIEW_ONLY => "Faqat ko'rish",
            self::BLOCKED => 'Bloklangan',
            self::SUSPENDED => "To'xtatilgan",
        ];
    }

    public function statusLabel(): string
    {
        return __(self::statusLabels()[$this->status] ?? $this->status);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(ClinicStatusLog::class)->latest('id');
    }

    public function balanceTransactions()
    {
        return $this->hasMany(BalanceTransaction::class)->latest('id');
    }

    public function setting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function patientCount(): int
    {
        return Patient::withoutGlobalScopes()->where('clinic_id', $this->id)->count();
    }

    public function trialDaysLeft(): int
    {
        if (! $this->trial_ends_at) {
            return 0;
        }

        return max(0, (int) ceil(($this->trial_ends_at->getTimestamp() - now()->getTimestamp()) / 86400));
    }

    public function isReadOnly(): bool
    {
        return in_array($this->status, [self::VIEW_ONLY, self::BLOCKED, self::SUSPENDED], true);
    }
}
