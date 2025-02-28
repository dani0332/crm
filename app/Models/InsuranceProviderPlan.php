<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class InsuranceProviderPlan extends BaseModel
{
    use HasFactory;

    protected $table = 'insurance_provider_plans';

    function scopeActive($query){
        return $query->where('is_active', 1);
    }
}
