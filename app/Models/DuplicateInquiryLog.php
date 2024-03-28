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

    /**
     * @return string
     */
    public function getCreatedAtAttribute($table)
    {
        return $this->asDateTime($table)->timezone(config('app.timezone'))
            ->format(config('constants.datetime_format'));
    }

    /**
     * @return string
     */
    public function getUpdatedAtAttribute($table)
    {
        return $this->asDateTime($table)->timezone(config('app.timezone'))
            ->format(config('constants.datetime_format'));
    }
}
