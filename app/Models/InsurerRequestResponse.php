<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsurerRequestResponse extends Model
{
    use HasFactory;
    protected $table = 'insurer_request_response';
    public function carQuotePlanDetails()
    {
        return $this->belongsTo(CarQuotePlanDetail::class, 'provider_id', 'id');
    }
}
