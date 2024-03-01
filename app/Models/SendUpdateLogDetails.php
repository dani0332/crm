<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SendUpdateLogDetails extends Model
{
    protected $casts = [
        'data' => 'array',
    ];

    protected $fillable = [
        'send_update_log_id', 'data', 'type',
    ];

    protected function sendUpdateLog(): BelongsTo
    {
        return $this->belongsTo(SendUpdateLog::class);
    }
}
