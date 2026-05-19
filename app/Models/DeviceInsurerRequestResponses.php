<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class DeviceInsurerRequestResponses extends Model
{
    protected $connection = 'mongodb';
    protected $table = 'device-insurer-request-responses';
    protected $casts = ['createdAt' => 'datetime:Y-m-d', 'updatedAt' => 'datetime:Y-m-d'];

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class, 'provider_id', 'id');
    }
}
