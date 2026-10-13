<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;

class LabOrder extends Model
{
    use BelongsToClinic, Auditable;

    protected $guarded = [];

    protected $casts = ['ordered_at' => 'datetime', 'completed_at' => 'datetime'];

    public function patient()
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
}
