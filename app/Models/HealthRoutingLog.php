<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthRoutingLog extends Model
{
    protected $fillable = [
        'quote_request_id',
        'uuid',
        'type',
        'team_category',
        'logged_by',
        'log_data',
        'source',
    ];
    protected $casts = [
        'log_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }

    public function getCreatedAtAttribute($value): string
    {
        return Carbon::parse($value)->format('m/d/Y H:i:s');
    }
}
