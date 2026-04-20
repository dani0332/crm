<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class HealthUMAFResponse extends Model
{
    protected $connection = 'mongodb';
    protected $table = 'health-umaf-responses';
    protected $casts = ['createdAt' => 'datetime:Y-m-d', 'updatedAt' => 'datetime:Y-m-d'];

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class, 'provider_id', 'id');
    }

    public function quote()
    {
        return $this->belongsTo(HealthQuote::class, 'quote_uuid', 'uuid');
    }
}
