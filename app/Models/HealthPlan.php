<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthPlan extends Model
{
    use HasFactory;

    protected $table = 'health_plan';

    public function provider_id()
    {
        return $this->hasOne(InsuranceProvider::class, 'id', 'provider_id');
    }
}
