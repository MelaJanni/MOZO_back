<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'event',
        'auditable_type',
        'auditable_id',
        'user_id',
        'old_values',
        'new_values',
        'user_agent',
        'ip_address',
        'metadata',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
    ];

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(string $event, Model $model, ?array $oldValues = null, ?array $newValues = null, ?array $metadata = null)
    {
        return self::create([
            'event' => $event,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'user_id' => auth()->id(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_agent' => request()->userAgent(),
            'ip_address' => request()->ip(),
            'metadata' => $metadata,
        ]);
    }

    public function getChangesAttribute(): array
    {
        $changes = [];

        if ($this->old_values && $this->new_values) {
            foreach ($this->new_values as $key => $newValue) {
                $oldValue = $this->old_values[$key] ?? null;
                if ($oldValue !== $newValue) {
                    $changes[$key] = [
                        'old' => $oldValue,
                        'new' => $newValue
                    ];
                }
            }
        }

        return $changes;
    }
}
