<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LifePlanRider extends Model
{
    protected $table = 'life_plan_rider';

    public function plan()
    {
        return $this->belongsTo(InsuranceProviderPlan::class, 'plan_id');
    }

    public function riderOption()
    {
        return $this->belongsTo(LifeRiderOption::class, 'rider_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function scopeInActive($query)
    {
        return $query->where('is_active', 0);
    }
}
