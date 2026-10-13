<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;

class PatientPhoto extends Model
{
    use BelongsToClinic, Auditable;

    protected $guarded = [];

    public function patient()
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }
}
