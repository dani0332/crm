<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Config;

class TravelQuote extends Model implements AuditableContract
{
    use HasFactory, Auditable;
    protected $table = 'travel_quote_request';
    protected $guarded = [];

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
    public function quoteStatus()
    {
        return $this->hasOne(QuoteStatus::class, 'id', 'quote_status_id');
    }
    public function travelQuoteRequestDetail()
    {
        return $this->hasOne(TravelQuoteRequestDetail::class, 'travel_quote_request_id', 'id');
    }
    public function customerAdditionalInfo()
    {
        return $this->hasMany(CustomerAdditionalInfo::class, 'quote_request_id', 'id');
    }

    public function documents()
    {
        return $this->morphMany(QuoteDocument::class, 'quote_documentable');
    }
    public function payments()
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    public function plan()
    {
        return $this->belongsTo(TravelPlan::class, 'plan_id');
    }

}
