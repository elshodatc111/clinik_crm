<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function rangeLabel(): string
    {
        return $this->max_patients
            ? $this->min_patients.' – '.$this->max_patients
            : $this->min_patients.'+';
    }
}
