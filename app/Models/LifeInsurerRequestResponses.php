<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class LifeInsurerRequestResponses extends Model
{
    protected $connection = 'mongodb';
    protected $table = 'life-insurer-request-responses';
    protected $casts = ['createdAt' => 'datetime:Y-m-d', 'updatedAt' => 'datetime:Y-m-d'];

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class, 'provider_id', 'id');
    }
}
