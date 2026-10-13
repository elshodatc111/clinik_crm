<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;

class CashClosing extends Model
{
    use BelongsToClinic, Auditable;

    protected $guarded = [];

    protected $casts = ['date' => 'date', 'closed_at' => 'datetime'];
}
