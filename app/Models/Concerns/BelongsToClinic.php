<?php

namespace App\Models\Concerns;

use App\Models\Clinic;
use App\Support\Tenant;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToClinic
{
    protected static function bootBelongsToClinic(): void
    {
        static::addGlobalScope('clinic', function (Builder $q) {
            $id = Tenant::id();
            $table = $q->getModel()->getTable();
            if ($id) {
                $q->where($table.'.clinic_id', $id);
            } elseif (auth()->check() && auth()->user()->role !== 'super_admin') {
                // Klinikasiz oddiy foydalanuvchi hech narsani ko'rmaydi
                $q->whereRaw('1 = 0');
            }
        });

        static::creating(function ($model) {
            if (empty($model->clinic_id)) {
                $model->clinic_id = Tenant::id();
            }
        });
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }
}
