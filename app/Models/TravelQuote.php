<?php

namespace App\Models;

use App\Enums\FilterTypes;
use App\Traits\FilterCriteria;
use App\Traits\QuoteModelTrait;
use Config;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class TravelQuote extends Model implements AuditableContract
{
    use HasFactory, FilterCriteria, Auditable, QuoteModelTrait;

    protected $table = 'travel_quote_request';
    protected $guarded = [];
    public $filterables = [
        'first_name' => FilterTypes::EXACT,
        'last_name' => FilterTypes::FREE,
        'uuid' => FilterTypes::EXACT,
        'code' => FilterTypes::EXACT,
        'email' => FilterTypes::EXACT,
        'mobile_no' => FilterTypes::EXACT,
        'created_at' => FilterTypes::DATE_BETWEEN,
        'quote_status_id' => FilterTypes::IN,
        'advisor_id' => FilterTypes::IN,
    ];

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

    public function travelCoverFor()
    {
        return $this->belongsTo(TravelCoverFor::class, 'travel_cover_for_id');
    }

    public function regionCoverFor()
    {
        return $this->belongsTo(Regions::class, 'region_cover_for_id');
    }

    public function currentlyLocatedIn()
    {
        return $this->belongsTo(CurrentlyLocatedIn::class, 'currently_located_in_id');
    }

    public function nationality()
    {
        return $this->belongsTo(Nationality::class, 'nationality_id');
    }

    public function destination()
    {
        return $this->belongsTo(Nationality::class, 'destination_id');
    }

    public function advisor()
    {
        return $this->belongsTo(User::class, 'advisor_id');
    }

    public function paymentStatus()
    {
        return $this->belongsTo(PaymentStatus::class, 'payment_status_id');
    }

    public function getPreviousPolicyExpiryDateAttribute($table)
    {
        $date_time_format = Config::get('constants.datetime_format');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }
}
