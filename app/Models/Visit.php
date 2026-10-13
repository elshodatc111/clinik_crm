<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    use BelongsToClinic, Auditable;

    protected $guarded = [];

    protected $casts = ['visited_at' => 'datetime'];

    public function patient()
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
