<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use BelongsToClinic, Auditable;

    protected $guarded = [];

    protected $casts = ['paid_at' => 'datetime', 'percent' => 'float'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
