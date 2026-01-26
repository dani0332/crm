<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthRoutingLog extends Model
{
    protected $fillable = [
        'quote_request_id',
        'uuid',
        'type',
        'team_category',
        'log_data',
    ];
}
