<?php

namespace App\Models;

use App\Enums\FilterTypes;
use App\Enums\QuoteTypeId;
use App\Events\QuoteEmailUpdated;
use App\Traits\FilterCriteria;
use App\Traits\QuoteModelTrait;
use Config;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class TravelQuote extends Model implements AuditableContract
{
    use Auditable, FilterCriteria, HasFactory, QuoteModelTrait;

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
        'renewal_batch' => FilterTypes::EXACT,
        'quote_status_id' => FilterTypes::IN,
        'is_ecommerce' => FilterTypes::EXACT,
        'previous_quote_policy_number' => FilterTypes::NULL_CHECK,
        'advisor_id' => FilterTypes::IN,
        'policy_number' => FilterTypes::EXACT,
        'source' => FilterTypes::EXACT,
        'renewal_expiry_date' => FilterTypes::DATE_BETWEEN,
    ];
    protected $dispatchesEvents = [
        'updated' => QuoteEmailUpdated::class,
    ];

    public function quoteStatus()
    {
        return $this->belongsTo(QuoteStatus::class);
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

    public function parent()
    {
        return $this->belongsTo(TravelQuote::class, 'parent_id');
    }

    public function child()
    {
        return $this->hasOne(TravelQuote::class, 'parent_id');
    }

    public function quotePlan()
    {
        return $this->hasMany(TravelQuotePlan::class, 'travel_quote_request_id');
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
        return $this->belongsTo(CurrentlyLocatedIn::class);
    }

    public function nationality()
    {
        return $this->belongsTo(Nationality::class);
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
        return $this->belongsTo(PaymentStatus::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function getPreviousPolicyExpiryDateAttribute($table)
    {
        $date_time_format = Config::get('constants.datetime_format');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class, 'insurance_provider_id', 'id')->select(['id', 'text']);
    }
    /**
     * get data by personal quote type.
     *
     * @return mixed
     */
    public function scopeByQuoteTypeCode($query, $quoteTypeCode)
    {
        return $query->whereHas('quoteType', function ($q) use ($quoteTypeCode) {
            $q->where('code', ($quoteTypeCode));
        });
    }

    /**
     * @return mixed
     */
    public function scopeByQuoteTypeId($query, $quoteTypeId)
    {
        return $query->where('quote_type_id', $quoteTypeId);
    }

    public function quoteRequestEntityMapping()
    {
        return $this->hasOne(QuoteRequestEntityMapping::class, 'quote_request_id')
            ->where('quote_type_id', QuoteTypeId::Travel);
    }

    public function sageApiLogs()
    {
        return $this->morphMany(SageApiLog::class, 'section');
    }
    public function activities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Activities::class, 'quote_request_id')
            ->where('quote_type_id', QuoteTypeId::Travel);

    }

    public function customerMembers()
    {
        return $this->morphMany(CustomerMembers::class, 'quote');
    }

    public function transactionType()
    {
        return $this->belongsTo(Lookup::class, 'transaction_type_id', 'id');
    }

    public function policyWording()
    {
        return $this->hasMany(TravelPlanPolicyWording::class, 'plan_id', 'plan_id');
    }
}
