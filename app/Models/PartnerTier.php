<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'code', 'sort_order', 'status'])]
class PartnerTier extends Model
{
    public function partners(): HasMany
    {
        return $this->hasMany(Partner::class);
    }

    protected function casts(): array
    {
        return [
            'status' => RecordStatus::class,
        ];
    }
}
