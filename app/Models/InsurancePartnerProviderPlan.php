<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsurancePartnerProviderPlan extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function scopeActiveForPlan(Builder $query, ?int $planId): Builder
    {
        if ($planId === null) {
            return $query->whereNull('id');
        }

        return $query->where('plan_id', $planId)
            ->where('is_active', true);
    }
}
