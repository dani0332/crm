<?php

namespace App\Models;

use App\Enums\FilterTypes;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypeId;
use App\Enums\TravelQuoteEnum;
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
        'policy_expiry_date' => FilterTypes::DATE_BETWEEN,
    ];
    protected $dispatchesEvents = [
        'updated' => QuoteEmailUpdated::class,
    ];
    protected $appends = [
        'insurer_api_status',
        'api_issuance_status',
        'insurer_api_email_action',
    ];

    protected static function booted()
    {
        static::updating(function ($model) {
            $skipBookingDateUpdateForNonCPD = true;
            if (isset(request()->sendUpdateId)) {
                $travelQuote = new TravelQuote;
                $endorsmentDetails = $travelQuote->isCPDEndorsment(request()->sendUpdateId);
                if ($endorsmentDetails['isCPDEndorsment']) {
                    info('Book Update - Policy Booking Date update is allowed for CPD Endorsment. Old PBD ('.$model->getOriginal('policy_booking_date').') - New PBD ('.$model->policy_booking_date.'). QuoteType: '.request()->quoteType.' - QuoteUUID: '.request()->quoteUuid.' - SendUpdateUUID: '.$endorsmentDetails['sendUpdateUUID']);
                    $skipBookingDateUpdateForNonCPD = false;
                }
            }

            if ($model->isDirty('policy_booking_date') && $model->getOriginal('policy_booking_date') && $skipBookingDateUpdateForNonCPD) {
                info($model->code.' updating the value of policy_booking_date is skipped. tried to change policy_booking_date from '.$model->getOriginal('policy_booking_date').' to '.$model->policy_booking_date);
                unset($model->policy_booking_date); // lock the policy booking date field
            }
        });
    }

    public function getApiIssuanceStatusAttribute()
    {
        return $this->api_issuance_status_id ? PolicyIssuanceEnum::getAPIIssuanceStatuses($this->api_issuance_status_id) : null;
    }
    public function getInsurerApiStatusAttribute()
    {
        return $this->insurer_api_status_id ? PolicyIssuanceEnum::getInsurerAPIStatuses($this->insurer_api_status_id) : null;
    }
    public function getInsurerApiEmailActionAttribute()
    {
        return $this->insurer_api_status_id ? PolicyIssuanceEnum::getInsurerAPIEmailActionMessage($this->insurer_api_status_id) : null;
    }

    public function getAuditables()
    {
        return [
            'auditable_type' => self::class,
        ];
    }
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
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function child()
    {
        return $this->hasOne(self::class, 'parent_id');
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

    public function TravelDestinations()
    {
        return $this->hasMany(TravelDestination::class, 'quote_id', 'id');
    }

    public function embeddedTransaction()
    {
        return $this->hasOne(EmbeddedTransaction::class, 'code', 'code');
    }

    public function isMultiTrip()
    {
        return $this->coverage_code === TravelQuoteEnum::COVERAGE_CODE_MULTI_TRIP;
    }

    public function scopeFilterBySegment($query, $alias = 'tqr')
    {
        $segmentFilter = request()->input('segment_filter');
        self::applySegmentFilter($query, $segmentFilter, $alias, QuoteTypeId::Travel);
    }

    public function travelQuotePlanDetails()
    {
        return $this->hasMany(TravelQuotePlanDetail::class, 'travel_quote_request_id');
    }

    public function policyIssuance()
    {
        return $this->morphOne(PolicyIssuance::class, 'model');
    }

    public function sageProcess()
    {
        return $this->morphOne(SageProcess::class, 'model');
    }

    public function isSingleTrip()
    {
        return $this->coverage_code === TravelQuoteEnum::COVERAGE_CODE_SINGLE_TRIP;
    }

    public function isAnnualTrip()
    {
        return $this->coverage_code === TravelQuoteEnum::COVERAGE_CODE_ANNUAL_TRIP;
    }

    public function isParent()
    {
        return is_null($this->parent_id) || empty($this->parent_id);
    }

    public function isChild()
    {
        return ! $this->isParent();
    }

    public function isAutomationCompleted()
    {
        return $this->policyIssuance?->status === PolicyIssuanceEnum::COMPLETED_STATUS;
    }

    public function isBookingFailed()
    {
        return $this->insurer_api_status_id === PolicyIssuanceEnum::BOOKING_DETAILS_API_FAILED_STATUS_ID;
    }

    public function isPolicyIssuanceFailed()
    {
        return in_array($this->insurer_api_status_id, [
            PolicyIssuanceEnum::AUTO_CAPTURE_FAILED_STATUS_ID,
            PolicyIssuanceEnum::POLICY_DETAIL_API_FAILED_STATUS_ID,
            PolicyIssuanceEnum::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID,
        ]);
    }

    public function hasChild()
    {
        return $this->child()->exists();
    }

    public function isAdult()
    {
        return $this->customerMembers->where('age', '<', 65)->count() > 0;
    }

    public function isSenior()
    {
        return $this->customerMembers->where('age', '>=', 65)->count() > 0;
    }
}
