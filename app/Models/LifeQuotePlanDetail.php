<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class LifeQuotePlanDetail extends Model
{
    protected $connection = 'mongodb';
    protected $table = 'life-quote-plan-details';

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class, 'providerId', 'id');
    }
}
