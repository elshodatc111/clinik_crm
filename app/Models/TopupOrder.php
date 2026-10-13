<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TopupOrder extends Model
{
    protected $guarded = [];

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }
}
