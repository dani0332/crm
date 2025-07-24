<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InsuranceProviderPlan extends Model
{
    use HasFactory;

    protected $table = 'insurance_provider_plans';
    protected $guarded = [];

    public function policyWordings(): HasOne
    {
        return $this->hasOne(PolicyWording::class, 'plan_id');
    }
}
