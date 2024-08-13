<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsurerRequestResponse extends Model
{
    use HasFactory;

    protected $table = 'insurer_request_response';
    protected $primaryKey = 'id';
    protected $guarded = [];

    public function carQuotePlanDetails()
    {
        return $this->belongsTo(CarQuotePlanDetail::class, 'provider_id', 'id');
    }

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class, 'provider_id', 'id');
    }
}
