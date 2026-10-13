<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use BelongsToClinic, Auditable;

    protected $guarded = [];

    public static function statuses(): array
    {
        return [
            'unpaid' => "To'lanmagan",
            'partial' => "Qisman to'langan",
            'paid' => "To'langan",
            'canceled' => 'Bekor qilingan',
        ];
    }

    public static function methods(): array
    {
        return [
            'cash' => 'Naqd',
            'card' => 'Karta',
            'payme' => 'Payme',
            'click' => 'Click',
            'transfer' => "Bank o'tkazmasi",
            'other' => 'Boshqa',
        ];
    }

    public function statusLabel(): string
    {
        return __(self::statuses()[$this->status] ?? $this->status);
    }

    public function netTotal(): int
    {
        return max(0, (int) $this->total - (int) $this->discount);
    }

    public function remaining(): int
    {
        return max(0, $this->netTotal() - (int) $this->paid);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    public static function nextNumber(int $clinicId): string
    {
        $prefix = 'INV-'.now()->format('ymd').'-';
        $n = static::withoutGlobalScopes()->where('clinic_id', $clinicId)->where('number', 'like', $prefix.'%')->count() + 1;

        return $prefix.str_pad((string) $n, 3, '0', STR_PAD_LEFT);
    }
}
