<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SendUpdateStatusLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'send_update_log_id',
        'previous_status',
        'current_status',
        'created_by',
    ];

    public function sendUpdateLog(): BelongsTo
    {
        return $this->belongsTo(SendUpdateLog::class, 'send_update_log_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
