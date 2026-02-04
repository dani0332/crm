<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class HealthInsurerRequestResponse extends Model
{
    protected $connection = 'mongodb';
    protected $table = 'health-insurer-request-responses';
    protected $casts = ['createdAt' => 'datetime:Y-m-d', 'updatedAt' => 'datetime:Y-m-d'];

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class, 'providerId', 'id');
    }

    public function quote()
    {
        return $this->belongsTo(HealthQuote::class, 'quoteUuid', 'uuid');
    }
}
