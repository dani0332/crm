<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
