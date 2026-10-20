<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A product Category (e.g. Health Product, Services, Book). Named "ProductType"
 * for historical reasons; surfaced to users as "Category".
 */
#[Fillable(['name', 'code', 'status'])]
class ProductType extends Model
{
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
