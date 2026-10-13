<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;

class CashMovement extends Model
{
    use BelongsToClinic, Auditable;

    protected $guarded = [];

    protected $casts = ['date' => 'date'];

    public static function categories(): array
    {
        return ['expense' => 'Xarajat', 'salary' => 'Ish haqi', 'advance' => 'Avans', 'refund' => 'Avansni qaytarish', 'other' => 'Boshqa'];
    }
}
