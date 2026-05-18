<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthPricingLog extends Model
{
    protected $casts = [
        'criteria' => 'array',
        'result' => 'array',
    ];

    public function member()
    {
        return $this->belongsTo(CustomerMembers::class, 'customer_member_id');
    }
}
