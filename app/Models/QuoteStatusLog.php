<?php

namespace App\Models;

use Config;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteStatusLog extends Model
{
    use HasFactory;

    protected $table = 'quote_status_log';
    protected $fillable = ['quote_type_id', 'quote_request_id', 'current_quote_status_id', 'created_at', 'updated_at', 'previous_quote_status_id', 'notes', 'created_by'];

    /**
     * @return void
     */
    public function setCreatedByIdAttribute()
    {
        $this->attributes['created_by_id'] = auth()->id();
    }

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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function previousQuoteStatus()
    {
        return $this->belongsTo(QuoteStatus::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currentQuoteStatus()
    {
        return $this->belongsTo(QuoteStatus::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function quoteType()
    {
        return $this->belongsTo(QuoteType::class, 'quote_type_id');
    }

    public function quoteStatus()
    {
        return $this->belongsTo(QuoteStatus::class, 'current_quote_status_id');
    }
}
