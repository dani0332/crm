<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuyLeadRequest extends Model
{
    protected $fillable = [
        'quote_type_id',
        'user_id',
        'requested_count',
        'allocated_count',
        'value_cost_per_lead',
        'volume_cost_per_lead',
        'expires_at',
    ];
    protected $casts = [
        'requested_count' => 'integer',
        'allocated_count' => 'integer',
        'value_cost_per_lead' => 'float',
        'volume_cost_per_lead' => 'float',
        'expires_at' => 'datetime',
    ];

    public function logs()
    {
        return $this->hasMany(BuyLeadRequestLog::class);
    }
}
