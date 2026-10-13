<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use BelongsToClinic, Auditable;

    protected $guarded = [];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime'];

    public static function statuses(): array
    {
        return [
            'scheduled' => 'Kutilmoqda',
            'arrived' => 'Keldi',
            'in_progress' => 'Qabulda',
            'done' => 'Tugadi',
            'no_show' => 'Kelmadi',
            'canceled' => 'Bekor qilindi',
        ];
    }

    public function statusLabel(): string
    {
        return __(self::statuses()[$this->status] ?? $this->status);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function visit()
    {
        return $this->hasOne(Visit::class);
    }
}
