<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Config;

class SendUpdateStatusLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'send_update_log_id',
        'previous_status',
        'current_status',
        'created_by',
        'notes',
    ];
    protected $appends = [
        'previous_status_display',
        'current_status_display',
    ];

    public function getPreviousStatusDisplayAttribute(): string
    {
        return $this->formatStatusLabel($this->previous_status);
    }

    public function getCurrentStatusDisplayAttribute(): string
    {
        return $this->formatStatusLabel($this->current_status);
    }

    protected function formatStatusLabel(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return ucwords(str_replace('_', ' ', strtolower($value)));
    }

    public function getCreatedAtAttribute($table)
    {
        $date_time_format = Config::get('constants.datetime_format');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }

    public function sendUpdateLog(): BelongsTo
    {
        return $this->belongsTo(SendUpdateLog::class, 'send_update_log_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
