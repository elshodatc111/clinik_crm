<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use BelongsToClinic, Auditable;

    protected $guarded = [];

    protected $casts = ['paid_at' => 'datetime'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }
}
