<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsurancePlansEligibility extends Model
{
    protected $table = 'insurance_plans_eligibility';
    protected $guarded = [];

    public function plan()
    {
        return $this->belongsTo(InsuranceProviderPlan::class, 'plan_id');
    }
}
