<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use BelongsToClinic, Auditable, SoftDeletes;

    protected $guarded = [];

    protected $casts = ['birth_date' => 'date'];

    public function getFullNameAttribute(): string
    {
        return trim($this->last_name.' '.$this->first_name.' '.$this->middle_name);
    }

    public function age(): ?int
    {
        return $this->birth_date?->age;
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class)->latest('visited_at');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class)->latest('id');
    }

    public function labOrders()
    {
        return $this->hasMany(LabOrder::class)->latest('id');
    }

    public static function normalizeName(string $s): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $s)));
    }

    /** Dublikat topish: JSHSHIR yoki (F.I.Sh. + tug'ilgan sana) bir xil. */
    public static function findDuplicate(array $d, ?int $exceptId = null): ?self
    {
        $q = static::withTrashed();
        if ($exceptId) {
            $q->where('id', '!=', $exceptId);
        }
        if (! empty($d['pinfl'])) {
            $hit = (clone $q)->where('pinfl', $d['pinfl'])->first();
            if ($hit) {
                return $hit;
            }
        }
        if (! empty($d['birth_date'])) {
            $hit = (clone $q)->whereDate('birth_date', $d['birth_date'])
                ->whereRaw('LOWER(TRIM(last_name)) = ?', [static::normalizeName($d['last_name'])])
                ->whereRaw('LOWER(TRIM(first_name)) = ?', [static::normalizeName($d['first_name'])])
                ->where(function ($w) use ($d) {
                    $mid = static::normalizeName($d['middle_name'] ?? '');
                    $w->whereRaw("LOWER(TRIM(COALESCE(middle_name,''))) = ?", [$mid]);
                })
                ->first();

            return $hit;
        }

        return null;
    }
}
