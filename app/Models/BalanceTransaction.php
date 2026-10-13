<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BalanceTransaction extends Model
{
    const UPDATED_AT = null;

    protected $guarded = [];

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }
}
