<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $guarded = [];

    protected $casts = ['read_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function levelColor(): string
    {
        return ['success' => 'emerald', 'warning' => 'amber', 'danger' => 'red'][$this->level] ?? 'blue';
    }
}
