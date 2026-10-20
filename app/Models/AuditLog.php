<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['actor_id', 'auditable_type', 'auditable_id', 'event', 'old_values', 'new_values', 'ip_address', 'user_agent'])]
class AuditLog extends Model
{
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    protected function casts(): array
    {
        return [
            'new_values' => 'array',
            'old_values' => 'array',
        ];
    }
}
