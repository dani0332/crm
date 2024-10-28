<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuyLeadRequestLog extends Model
{
    protected $fillable = [
        'buy_lead_request_id',
        'quote_type_id',
        'quote_id',
        'uuid',
        'cost_per_lead',
        're_assigned_at',
        're_assigned_to',
        're_assignment_reason',
    ];

    public function request()
    {
        return $this->belongsTo(BuyLeadRequest::class);
    }
}
