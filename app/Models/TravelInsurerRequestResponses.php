<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class TravelInsurerRequestResponses extends Model
{
    protected $connection = 'mongodb';
    protected $table = 'travel-insurer-request-responses';
    protected $casts = ['createdAt' => 'datetime:Y-m-d', 'updatedAt' => 'datetime:Y-m-d'];

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class, 'provider_id', 'id');
    }
}
