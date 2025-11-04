<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function get($key, $default = null)
    {
        $setting = static::where('key', $key)->where('is_active', true)->first();

        if (!$setting) {
            return $default;
        }

        return match($setting->type) {
            'json' => json_decode($setting->value, true),
            'boolean' => (bool) $setting->value,
            'integer' => (int) $setting->value,
            'float' => (float) $setting->value,
            default => $setting->value,
        };
    }

    public static function set($key, $value, $type = 'text', $group = null, $label = null, $description = null)
    {
        $processedValue = match($type) {
            'json' => is_string($value) ? $value : json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };

        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $processedValue,
                'type' => $type,
                'group' => $group,
                'label' => $label ?? $key,
                'description' => $description,
                'is_active' => true
            ]
        );
    }

    public static function getGroup($group)
    {
        return static::where('group', $group)
                    ->where('is_active', true)
                    ->get()
                    ->keyBy('key')
                    ->map(fn($setting) => static::get($setting->key));
    }
}
