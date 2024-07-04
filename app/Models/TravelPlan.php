<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TravelPlan extends Model
{
    use HasFactory;

    protected $table = 'travel_plan';

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class, 'provider_id');
    }
}
