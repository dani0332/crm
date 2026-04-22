<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthRateControl extends Model
{
    protected $table = 'health_rates_control';
    protected $fillable = [
        'health_plan_id',
        'version',
        'status',
        'effective_from',
        'effective_to',
        'created_by',
    ];
}
