<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Config;

class QuoteStatusLog extends Model
{
    use HasFactory;
    protected $table = 'quote_status_log';
    protected $fillable = ['quote_type_id', 'quote_request_id', 'current_quote_status_id', 'created_at', 'updated_at', 'previous_quote_status_id'];

    public function getCreatedAtAttribute($table)
    {
        $date_time_format = Config::get('constants.datetime_format');
        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }
    public function getUpdatedAtAttribute($table)
    {
        $date_time_format = Config::get('constants.datetime_format');
        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }
}
