<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = ['changes' => 'array'];

    /** Xavfsizlik voqeasini yozish (kirish, chiqish, 2FA, impersonatsiya). Hech qachon asosiy ishni buzmaydi. */
    public static function record(string $action, ?int $userId = null, ?int $clinicId = null, array $info = []): void
    {
        try {
            static::create([
                'clinic_id' => $clinicId, 'user_id' => $userId ?? auth()->id(), 'action' => mb_substr($action, 0, 20),
                'auditable_type' => 'Security', 'auditable_id' => $userId ?? auth()->id(), 'changes' => $info ?: null,
                'ip_address' => request()?->ip(),
            ]);
        } catch (\Throwable $e) {
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }
}
