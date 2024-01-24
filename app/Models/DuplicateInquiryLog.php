<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DuplicateInquiryLog extends Model
{
    protected $table = 'duplicate_inquiry_logs';
    protected $fillable = ['created_at'];

    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }
}
