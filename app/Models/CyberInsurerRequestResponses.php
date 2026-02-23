<?php

namespace App\Models;

class CyberInsurerRequestResponses extends BaseMongoModel
{
    protected $table = 'cyber-insurer-request-responses';
    protected $casts = ['createdAt' => 'datetime:Y-m-d', 'updatedAt' => 'datetime:Y-m-d'];

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class, 'provider_id', 'id');
    }
}
