<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['promotion_id', 'customer_id', 'status', 'sent_at', 'responded_at'])]
class PromotionCustomer extends Model
{
    protected $table = 'promotion_customers';

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }
}
