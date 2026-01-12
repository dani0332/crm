<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessTypeOfInsurance extends Model
{
    protected $table = 'business_type_of_insurance';



    public function scopeActive($query)
    {
        $query->where('is_active', 1);
    }
}
