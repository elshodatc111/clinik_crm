<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use BelongsToClinic;

    protected $guarded = [];
}
