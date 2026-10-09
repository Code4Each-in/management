<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One configuration change made in the PMS. Never stores secrets. */
class UpworkConfigLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['user_id', 'entity', 'entity_id', 'label', 'action', 'changes'];

    protected $casts = [
        'changes' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }

    /**
     * Record a change. For updates pass the model before saving changes, or an
     * explicit {field: [old, new]} map.
     */
    public static function record(?int $userId, string $entity, ?int $entityId, ?string $label, string $action, ?array $changes = null): self
    {
        return static::create([
            'user_id' => $userId,
            'entity' => $entity,
            'entity_id' => $entityId,
            'label' => $label !== null ? mb_substr($label, 0, 150) : null,
            'action' => $action,
            'changes' => $changes ?: null,
        ]);
    }

    /** {field: [old, new]} for the dirty attributes of a model about to be saved. */
    public static function diff(Model $model, array $ignore = ['updated_at', 'created_at']): array
    {
        $changes = [];
        foreach ($model->getDirty() as $field => $new) {
            if (in_array($field, $ignore, true)) {
                continue;
            }
            $old = $model->getOriginal($field);
            if (is_string($old) && in_array(substr(ltrim($old), 0, 1), ['{', '['], true)) {
                $old = json_decode($old, true) ?? $old;
            }
            if (is_string($new) && in_array(substr(ltrim($new), 0, 1), ['{', '['], true)) {
                $new = json_decode($new, true) ?? $new;
            }
            if ($old != $new) {
                $changes[$field] = [$old, $new];
            }
        }

        return $changes;
    }
}
