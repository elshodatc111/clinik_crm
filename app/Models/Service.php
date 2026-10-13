<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use BelongsToClinic, Auditable;

    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];
}
