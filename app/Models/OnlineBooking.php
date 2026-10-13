<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;

class OnlineBooking extends Model
{
    use BelongsToClinic, Auditable;

    protected $guarded = [];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'hold_expires_at' => 'datetime', 'paid_at' => 'datetime', 'birth_date' => 'date'];

    public function getFullNameAttribute(): string
    {
        return trim($this->last_name.' '.$this->first_name.' '.$this->middle_name);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
