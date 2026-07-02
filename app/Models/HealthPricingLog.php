<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthPricingLog extends Model
{
    protected $casts = [
        'criteria' => 'array',
        'result' => 'array',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(CustomerMembers::class, 'customer_member_id')
            ->select(['id', 'first_name', 'last_name'])
            ->whereNull('deleted_at');
    }
}
