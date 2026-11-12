<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'customer_id',
    'partner_id',
    'full_name',
    'hp_no',
    'partner_id_input',
    'q1_income_growth',
    'q2_seeking_way',
    'q3_interested_tools',
    'remarks',
    'assigned_to_owner',
])]
class FeedbackSubmission extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'q1_income_growth' => 'boolean',
            'q2_seeking_way' => 'boolean',
            'q3_interested_tools' => 'boolean',
            'assigned_to_owner' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
