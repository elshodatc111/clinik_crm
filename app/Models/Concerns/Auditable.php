<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use App\Support\Tenant;

trait Auditable
{
    protected static function bootAuditable(): void
    {
        foreach (['created', 'updated', 'deleted'] as $event) {
            static::registerModelEvent($event, function ($model) use ($event) {
                $changes = match ($event) {
                    'updated' => $model->getChanges(),
                    'created' => $model->getAttributes(),
                    default => [],
                };
                unset($changes['updated_at'], $changes['created_at'], $changes['password']);
                if ($event === 'updated' && ! $changes) {
                    return;
                }
                AuditLog::create([
                    'clinic_id' => $model->clinic_id ?? Tenant::id(),
                    'user_id' => auth()->id(),
                    'action' => $event,
                    'auditable_type' => class_basename($model),
                    'auditable_id' => $model->getKey(),
                    'changes' => $changes,
                    'ip_address' => request()?->ip(),
                ]);
            });
        }
    }
}
