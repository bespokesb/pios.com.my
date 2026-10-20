<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'coverage_area', 'status'])]
class Territory extends Model
{
    public function partners(): HasMany
    {
        return $this->hasMany(Partner::class);
    }

    public function businessOwners(): HasMany
    {
        return $this->hasMany(BusinessOwner::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    protected function casts(): array
    {
        return [
            'status' => RecordStatus::class,
        ];
    }
}
