<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceProviderPlan extends Model
{
    use HasFactory;

    protected $table = 'insurance_provider_plans';
    protected $guarded = [];
}
