<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InsuranceProviderPlan extends Model
{
    use HasFactory;

    protected $table = 'insurance_provider_plans';

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class, 'provider_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function subType()
    {
        return $this->belongsTo(Lookup::class, 'sub_type_id');
    }

    protected $guarded = [];

    public function policyWordings(): HasOne
    {
        return $this->hasOne(PolicyWording::class, 'plan_id');
    }

    public function eligibilities()
    {
        return $this->hasMany(InsurancePlansEligibility::class, 'plan_id');
    }

    public function currencyCoverages()
    {
        return $this->hasMany(CurrencyCoverage::class, 'plan_id');
    }

    /**
     * Get rider options for this plan (from rider_option table)
     */
    public function riderOptions()
    {
        return $this->hasMany(RiderOption::class, 'plan_id');
    }

    /**
     * Get riders for this plan with rider details
     */
    public function riders()
    {
        return $this->hasMany(RiderOption::class, 'plan_id')->with('rider');
    }
}
