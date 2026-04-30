<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthRate extends Model
{
    protected $fillable = [
        'health_plan_id',
        'health_rate_control_id',
        'version',
        'health_plan_co_payment_id',
        'emirate_type',
        'min_age',
        'max_age',
        'gender',
        'marital_status',
        'cohort',
        'premium',
        'status',
        'is_active',
    ];

    public function healthPlan(): BelongsTo
    {
        return $this->belongsTo(HealthPlan::class, 'health_plan_id');
    }
}
