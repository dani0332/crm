<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InsurancePartnerProviderPlan extends Model
{
    public function scopeActiveForPlan(Builder $query, int $planId): Builder
    {
        return $query->where('plan_id', $planId)
            ->where('is_active', true);
    }
}
