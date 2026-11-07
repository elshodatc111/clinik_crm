<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpTelegramLink extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }
}
