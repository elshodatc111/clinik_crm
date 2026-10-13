<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public static function active(): ?self
    {
        return static::where('is_active', true)->orderByDesc('version')->first();
    }
}
