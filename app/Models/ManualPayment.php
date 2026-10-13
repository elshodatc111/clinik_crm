<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Kartaga qo'lda to'lov (obuna). Ataylab tenant-scope'siz: klinika tomoni har doim clinic_id bo'yicha filtrlaydi. */
class ManualPayment extends Model
{
    protected $guarded = [];

    protected $casts = ['paid_at' => 'date', 'reviewed_at' => 'datetime'];

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public static function statusLabel(string $s): string
    {
        return ['pending' => 'Tekshirilmoqda', 'approved' => 'Tasdiqlangan', 'rejected' => 'Bekor qilingan', 'canceled' => 'Klinika bekor qilgan'][$s] ?? $s;
    }

    public static function statusColor(string $s): string
    {
        return ['pending' => 'yellow', 'approved' => 'green', 'rejected' => 'red', 'canceled' => 'slate'][$s] ?? 'slate';
    }
}
