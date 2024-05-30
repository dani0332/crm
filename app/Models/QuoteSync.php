<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteSync extends Model
{
    use HasFactory;

    protected $table = 'quote_sync';
    protected $fillable = [
        'quote_uuid',
        'quote_type_id',
        'updated_fields',
        'is_synced',
        'synced_at',
        'status',
        'error',
    ];
    public function getCreatedAtAttribute($table)
    {
        $date_time_format = config('constants.datetime_format');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }

    public function getUpdatedAtAttribute($table)
    {
        $date_time_format = config('constants.datetime_format');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }
}
