<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $table = 'platform_settings';

    protected $guarded = [];

    public static function all_(): array
    {
        return Cache::rememberForever('platform_settings', fn () => static::query()->pluck('value', 'key')->all());
    }

    public static function get(string $key, $default = null)
    {
        $all = static::all_();

        return (isset($all[$key]) && $all[$key] !== '') ? $all[$key] : $default;
    }

    public static function set(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('platform_settings');
    }
}
