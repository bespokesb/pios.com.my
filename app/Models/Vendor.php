<?php

namespace App\Models;

use App\Casts\PhoneNumber;
use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'contact_name', 'phone', 'email', 'address', 'status'])]
class Vendor extends Model
{
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    protected function casts(): array
    {
        return [
            'status' => RecordStatus::class,
            'phone' => PhoneNumber::class,
        ];
    }
}
