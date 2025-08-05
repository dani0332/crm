<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OcrLog extends Model
{
    use HasFactory;

    protected $table = 'ocr_logs';
    protected $guarded = [];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'execution_time_ms' => 'integer',
        'user_id' => 'integer',
        'provider_id' => 'integer',
    ];

    public function ocrLoggable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function provider()
    {
        return $this->belongsTo(InsuranceProvider::class, 'provider_id');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDocumentType($query, $documentTypeCode)
    {
        return $query->where('document_type_code', $documentTypeCode);
    }

    public function getFormattedExecutionTimeAttribute()
    {
        if (!$this->execution_time_ms) {
            return 'N/A';
        }
        return number_format($this->execution_time_ms, 2) . ' ms';
    }

    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            'success' => 'success',
            'failed' => 'error',
            'processing' => 'warning',
            'skipped' => 'info',
            default => 'default',
        };
    }

    public function getUploadedThroughAttribute()
    {
        return $this->user_id ? 'IMCRM' : 'Other than IMCRM';
    }
} 