<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    public function __construct(private readonly ?Request $request = null) {}

    public function log(
        User $actor,
        string $event,
        Model $auditable,
        array $oldValues = [],
        array $newValues = [],
    ): AuditLog {
        return AuditLog::query()->create([
            'actor_id' => $actor->id,
            'auditable_type' => $auditable::class,
            'auditable_id' => $auditable->getKey(),
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $this->request?->ip(),
            'user_agent' => $this->request?->userAgent(),
        ]);
    }
}
