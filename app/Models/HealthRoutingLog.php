<?php

namespace App\Models;

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
    ];
    protected $appends = ['logged_by_name'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }

    public function getCreatedAtAttribute($value): string
    {
        return \Carbon\Carbon::parse($value)->format('m/d/Y H:i:s');
    }

    public function getLoggedByNameAttribute($value): string
    {
        return $this->user?->name ?? 'N/A';
    }
}
