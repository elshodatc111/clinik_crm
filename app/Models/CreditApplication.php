<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;

class CreditApplication extends Model
{
    use BelongsToClinic, Auditable;

    protected $guarded = [];
}
