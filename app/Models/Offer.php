<?php

namespace App\Models;

use App\Support\Legal;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public static function active(): ?self
    {
        return static::where('is_active', true)->orderByDesc('version')->first();
    }

    /** Joriy tildagi matn (rus tilida bo'lmasa — o'zbekcha), o'zgaruvchilar joylangan holda. */
    public function text(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $raw = ($locale === 'ru' && trim((string) $this->body_ru) !== '') ? $this->body_ru : $this->body;

        return Legal::render($raw, $this->created_at?->format('d.m.Y'));
    }

    /** Foydalanuvchi shu (yoki undan yangi) versiyani qabul qilganmi. */
    public function acceptedBy(User $u): bool
    {
        return OfferAcceptance::where('user_id', $u->id)->where('offer_version', '>=', $this->version)->exists();
    }
}
