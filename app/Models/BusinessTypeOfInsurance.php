<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessTypeOfInsurance extends Model
{
    use HasFactory;

    protected $table = 'business_type_of_insurance';

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}
