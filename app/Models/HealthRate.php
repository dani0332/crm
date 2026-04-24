<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
