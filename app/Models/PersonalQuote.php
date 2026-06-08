<?php

namespace App\Models;

use App\Enums\EaModelEnum;
use App\Enums\FilterTypes;
use App\Enums\GenderEnum;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Events\QuoteEmailUpdated;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Traits\Filterable;
use App\Traits\FilterCriteria;
use App\Traits\QuoteModelTrait;
use App\Traits\QuoteTraits\PersonalQuotable;
use App\Traits\SpatieActivityLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Config;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class PersonalQuote extends Model implements AuditableContract
{
    use Auditable, Filterable, FilterCriteria, HasFactory, PersonalQuotable, QuoteModelTrait, SpatieActivityLog;

    protected $guarded = [];
    protected $casts = [
        'ea_model' => EaModelEnum::class,
    ];
    public $filterables = [
        'first_name' => FilterTypes::EXACT,
        'last_name' => FilterTypes::EXACT,
        'uuid' => FilterTypes::EXACT,
        'code' => FilterTypes::EXACT,
        'email' => FilterTypes::EXACT,
        'mobile_no' => FilterTypes::EXACT,
        'created_at' => FilterTypes::DATE_BETWEEN,
        'renewal_batch' => FilterTypes::EXACT,
        'quote_status_id' => FilterTypes::IN,
        'insurer_aml_status' => FilterTypes::IN,
        'is_ecommerce' => FilterTypes::EXACT,
        'previous_quote_policy_number' => FilterTypes::NULL_CHECK,
        'previous_quote_policy_number_text' => FilterTypes::EXACT,
        'advisor_id' => FilterTypes::IN,
        'renewal_batch_id' => FilterTypes::IN,
        'policy_number' => FilterTypes::EXACT,
        'source' => FilterTypes::EXACT,
        'policy_expiry_date' => FilterTypes::DATE_BETWEEN,
        'is_cold' => FilterTypes::EXACT,
        'stale_at' => FilterTypes::NULL_CHECK,
        'previous_policy_expiry_date' => FilterTypes::DATE_BETWEEN,
        'enquiry_count' => FilterTypes::EXACT,
        'is_renewal_tier_email_sent' => FilterTypes::EXACT,
        'is_early_renewal' => FilterTypes::EXACT,
    ];
    protected $appends = ['age', 'gender_label', 'pc_qualified_formatted', 'api_issuance_status', 'insurer_api_status'];

    /**
     * @return BelongsTo
     */
    protected $dispatchesEvents = [
        'updated' => QuoteEmailUpdated::class,
    ];

    public $allowedColumns = [
        'first_name', 'last_name', 'email', 'mobile_no', 'source', 'dob', 'company_name', 'company_address',
        'customer_id', 'gender', 'nationality_id', 'payment_status_id', 'quote_status_id', 'device', 'reference_url', 'notes', 'created_at', 'updated_at', 'code', 'uuid', 'policy_number', 'advisor_id', 'premium', 'parent_duplicate_quote_id', 'renewal_batch', 'renewal_expiry_date', 'previous_quote_policy_number', 'renewal_import_code', 'previous_policy_expiry_date', 'previous_quote_policy_premium', 'policy_start_date', 'policy_issuance_date', 'paid_at', 'payment_status_date', 'quote_status_date', 'premium_authorized', 'premium_captured', 'premium_refunded', 'price_vat_not_applicable', 'price_without_vat', 'price_with_vat', 'vat', 'insurer_quote_number', 'policy_issuance_status_id', 'policy_issuance_status_other', 'kyc_decision',
        'sub_source_id', 'sub_source_options_id',
    ];

    protected static function booted()
    {
        static::updating(function ($model) {
            $skipBookingDateUpdateForNonCPD = true;
            if (isset(request()->sendUpdateId)) {
                $personalQuote = new PersonalQuote;
                $endorsmentDetails = $personalQuote->isCPDEndorsment(request()->sendUpdateId);
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

    public function quoteStatus()
    {
        return $this->belongsTo(QuoteStatus::class);
    }

    /**
     * @return BelongsTo
     */
    public function paymentStatus()
    {
        return $this->belongsTo(PaymentStatus::class);
    }

    /**
     * @return HasOne
     */
    public function quoteDetail()
    {
        return $this->hasOne(PersonalQuoteDetail::class);
    }

    /**
     * @return BelongsTo
     */
    public function advisor()
    {
        return $this->belongsTo(User::class, 'advisor_id')->select(['id', 'email', 'name', 'mobile_no', 'landline_no', 'profile_photo_path', 'calendar_link']);
    }

    public function leadGenerator()
    {
        return $this->belongsTo(User::class, 'lead_generator_id')->select(['id', 'email', 'name']);
    }

    public function expertAdvisor()
    {
        return $this->belongsTo(User::class, 'expert_advisor_id')->select(['id', 'email', 'name', 'mobile_no']);
    }

    /**
     * @return BelongsTo
     */
    public function quoteType()
    {
        return $this->belongsTo(QuoteType::class);
    }

    /**
     * car quote request relation.
     *
     * @return HasOne
     */
    public function carQuote()
    {
        return $this->hasOne(CarQuote::class, 'uuid', 'uuid');
    }

    /**
     * bike quote request relation.
     *
     * @return HasOne
     */
    public function bikeQuote()
    {
        return $this->hasOne(BikeQuote::class);
    }

    /**
     * @return HasOne
     */
    public function jetskiQuote()
    {
        return $this->hasOne(JetskiQuote::class);
    }

    /**
     * @return HasOne
     */
    public function cycleQuote()
    {
        return $this->hasOne(CycleQuote::class, 'personal_quote_id', 'id');
    }

    /**
     * @return HasOne
     */
    public function petQuote()
    {
        return $this->hasOne(PetQuote::class);
    }

    /**
     * @return HasOne
     */
    public function yachtQuote()
    {
        return $this->hasOne(YachtQuote::class);
    }

    /**
     * @return HasOne
     */
    public function lifeQuote()
    {
        return $this->hasOne(LifeQuote::class);
    }

    public function vehicleDriverDetail(): MorphOne
    {
        return $this->morphOne(VehicleDriverDetail::class, 'quoteable');
    }

    /**
     * @param  $date
     * @return string
     */
    public function getDobAttribute($value)
    {
        if (isset($value) && ! empty($value)) {
            $date_time_format = config('constants.DATE_FORMAT_ONLY');

            return Carbon::parse($value)->format($date_time_format);
        }

        return null;
    }

    /**
     * @return BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class)->select(['id', 'name', 'email']);
    }

    /**
     * @return BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class)->select(['id', 'name', 'email']);
    }

    /**
     * @return string
     */
    //    public function getPolicyStartDateAttribute($date)
    //    {
    //        return $this->asDateTime($date)->timezone(config('app.timezone'))->format(Config::get('constants.DATE_FORMAT'));
    //    }

    /**
     * @return string
     */
    //    public function getPolicyIssuanceDateAttribute($date)
    //    {
    //        return $this->asDateTime($date)->timezone(config('app.timezone'))->format(Config::get('constants.DATE_FORMAT'));
    //    }

    /**
     * @return string
     */
    public function getPreviousPolicyExpiryDateAttribute($date)
    {
        return ($date) ? $this->asDateTime($date)->timezone(config('app.timezone'))->format(Config::get('constants.DATE_FORMAT')) : null;
    }

    /**
     * @return BelongsTo
     */
    public function nationality()
    {
        return $this->belongsTo(Nationality::class);
    }

    public function plans()
    {
        return $this->belongsTo(PersonalPlan::class, 'plan_id');
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

    /**
     * @return MorphMany
     */
    public function documents()
    {
        return $this->morphMany(QuoteDocument::class, 'quote_documentable');
    }

    /**
     * @return MorphMany
     */
    public function payments()
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    /**
     * @return BelongsTo
     */
    public function currentlyInsuredWith()
    {
        return $this->belongsTo(InsuranceProvider::class, 'currently_insured_with_id');
    }

    /**
     * @return BelongsTo
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function leadHistory()
    {
        return $this->hasMany(QuoteStatusLog::class, 'quote_request_id');
    }

    public function transactionType()
    {
        return $this->belongsTo(Lookup::class, 'transaction_type_id', 'id');
    }

    public function quoteRequestEntityMapping()
    {
        return $this->hasOne(QuoteRequestEntityMapping::class, 'quote_request_id')
            ->whereIn('quote_type_id', getPersonalQuoteTypeIds());
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activities::class, 'quote_request_id')
            ->whereIn('quote_type_id', getPersonalQuoteTypeIds());
    }

    public function notes()
    {
        return $this->morphMany(QuoteNote::class, 'quote_noteable');
    }

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class, 'insurance_provider_id', 'id');
    }

    public function sageApiLogs()
    {
        return $this->morphMany(SageApiLog::class, 'section');
    }

    public function scopeFilterBySegment($query, $segmentFilter, $quoteTypeId)
    {
        self::applySegmentFilter($query, $segmentFilter, 'personal_quotes', $quoteTypeId);
    }

    public function carPlan()
    {
        return $this->belongsTo(CarPlan::class, 'plan_id');
    }

    public function emirates()
    {
        return $this->belongsTo(Emirate::class, 'emirate_of_registration_id');
    }

    public function claimHistory()
    {
        return $this->belongsTo(ClaimHistory::class, 'claim_history_id');
    }

    public function customerMembers()
    {
        return $this->morphMany(CustomerMembers::class, 'quote');
    }

    public function renewalBatchModel()
    {
        return $this->belongsTo(RenewalBatch::class, 'renewal_batch_id');
    }

    public function scopeSavings($query)
    {
        return $query->where('quote_type_id', QuoteTypes::SAVINGS->id());
    }

    public function savingsQuote()
    {
        return $this->hasOne(SavingsQuote::class);
    }

    public function age(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->dob ? Carbon::parse($this->dob)->age : null
        );
    }

    public function genderLabel(): Attribute
    {
        $genderLabel = null;

        if (GenderEnum::tryFrom($this->gender)) {
            $genderLabel = GenderEnum::tryFrom($this->gender)->label();
        }

        if (! $genderLabel) {
            $genderLabel = in_array(strtolower($this->gender), ['m', 'male']) ? 'Male' : 'Female';
        }

        return Attribute::make(
            get: fn () => $genderLabel
        );
    }

    public function customerInsured()
    {
        return $this->hasOne(CustomerInsured::class, 'quote_request_id', 'id')
            ->whereIn('quote_type_id', getPersonalQuoteTypeIds())
            ->active();
    }

    // Get the active insured record for this quote
    public function latestInsured(): HasOneThrough
    {
        return $this->hasOneThrough(
            Insured::class,
            CustomerInsured::class,
            'quote_request_id', // customer_insured.quote_request_id
            'id', // insured.id
            'id', // personal_quotes.id
            'insured_id' // customer_insured.insured_id
        )
            ->whereIn('customer_insured.quote_type_id', getPersonalQuoteTypeIds())
            ->where('customer_insured.is_active', true);
    }

    public function amlLogs()
    {
        return $this->hasMany(KycLog::class, 'quote_request_id', 'id')
            ->whereIn('quote_type_id', getPersonalQuoteTypeIds())->withTrashed();
    }

    public function homeQuote()
    {
        return $this->hasOne(HomeQuote::class, 'personal_quote_id', 'id');
    }

    public function allowedColumns()
    {
        return $this->allowedColumns;
    }

    public function insuranceProviderPlan()
    {
        return $this->belongsTo(InsuranceProviderPlan::class, 'plan_id')->select(['id', 'code', 'text', 'provider_id', 'sub_type_id']);
    }

    public function quoteCustomerPlan()
    {
        return $this->hasOne(QuoteCustomerPlan::class, 'quote_uuid', 'uuid');
    }

    public function embeddedTransactions()
    {
        return $this->morphMany(EmbeddedTransaction::class, 'quote_request');
    }

    public function isNonAdvisorEmailSent()
    {
        return ! is_null($this->non_advisor_email_sent_at);
    }

    /**
     * Get all quote status logs for this model
     *
     * @return MorphMany
     */
    public function quoteStatusLogs(): HasMany
    {
        return $this->hasMany(QuoteStatusLog::class, 'quote_request_id');
    }

    /**
     * Check if quote can be updated to transaction approved status
     * Only allowed if quote has both payment link sent and initiated status in history
     */
    public function canUpdateToTransactionApproved(): bool
    {
        return $this->hasPaymentLinkHistory();
    }

    /**
     * Check if quote has both payment link sent and initiated status in its history
     */
    public function hasPaymentLinkHistory(): bool
    {
        // Check for PaymentLinkSentToCustomer status
        $hasPaymentLinkSent = $this->quoteStatusLogs()
            ->where(function ($query) {
                $query->where('previous_quote_status_id', QuoteStatusEnum::PaymentLinkSentToCustomer)
                    ->orWhere('current_quote_status_id', QuoteStatusEnum::PaymentLinkSentToCustomer);
            })
            ->exists();

        // Check for PaymentInitiated status
        $hasPaymentInitiated = $this->quoteStatusLogs()
            ->where(function ($query) {
                $query->where('previous_quote_status_id', QuoteStatusEnum::PaymentInitiated)
                    ->orWhere('current_quote_status_id', QuoteStatusEnum::PaymentInitiated);
            })
            ->exists();

        // Return true only if both statuses exist in history
        return $hasPaymentLinkSent && $hasPaymentInitiated;
    }

    /**
     * Get all related passport/visa detail records via the quoteable polymorphic relation.
     *
     * @return MorphMany
     */
    public function passportVisaDetails(): MorphOne
    {
        return $this->morphOne(PassportVisaDetail::class, 'quoteable')->latest('updated_at');
    }

    /**
     * Check if any payment has IPL in its splits
     */
    public function hasInsurerPaymentLink(): bool
    {
        return $this->payments()
            ->whereHas('paymentSplits', function ($query) {
                $query->where('payment_method', PaymentMethodsEnum::InsurerPaymentLink);
            })
            ->exists();
    }

    /**
     * Get all payments that have IPL splits
     *
     * @return Collection
     */
    public function getPaymentsWithInsurerPaymentLink()
    {
        return $this->payments()
            ->whereHas('paymentSplits', function ($query) {
                $query->where('payment_method', PaymentMethodsEnum::InsurerPaymentLink);
            })
            ->get();
    }

    /**
     * Get the last payment with an IPL split
     *
     * @return Payment|null
     */
    public function getLastPaymentWithInsurerPaymentLink()
    {
        return $this->payments()
            ->whereHas('paymentSplits', function ($query) {
                $query->where('payment_method', PaymentMethodsEnum::InsurerPaymentLink);
            })
            ->latest()
            ->first();
    }

    /**
     * Get all IPL payment splits across all payments
     *
     * @return Collection
     */
    public function getAllInsurerPaymentLinkSplits()
    {
        $payments = $this->getPaymentsWithInsurerPaymentLink();

        return $payments->flatMap(function ($payment) {
            return $payment->paymentSplits()
                ->where('payment_method', PaymentMethodsEnum::InsurerPaymentLink)
                ->get();
        });
    }

    /**
     * Get all of the model's ftc email logs.
     */
    public function ftcEmailLogs(): MorphMany
    {
        return $this->morphMany(FtcEmailLog::class, 'quote_trackable');
    }

    public function deviceQuote()
    {
        return $this->hasOne(DeviceQuote::class, 'personal_quote_id', 'id')->with('deviceMake', 'deviceModel');
    }

    // *********************** Cyber Quote ***********************

    public function cyberQuote()
    {
        return $this->hasOne(CyberQuote::class, 'personal_quote_id', 'id');
    }

    public function cyberPlanDetail()
    {
        return $this->hasOne(CyberQuotePlanDetail::class, 'quoteUuid', 'uuid')->where('planId', $this->plan_id);
    }

    public function cyberPolicyWording()
    {
        return $this->hasOne(PolicyWording::class, 'plan_id', 'plan_id');
    }

    /**
     * Check if booking has failed
     *
     * @return bool
     */
    public function isBookingFailed()
    {
        return $this->insurer_api_status_id === PolicyIssuanceEnum::PIA_BOOK_POLICY_API_FAILED_STATUS_ID;
    }

    /**
     * Get the policy issuance for this quote
     *
     * @return MorphOne
     */
    public function policyIssuance()
    {
        return $this->morphOne(PolicyIssuance::class, 'model');
    }

    /**
     * Check if policy issuance has failed
     *
     * @return bool
     */
    public function isPolicyIssuanceFailed()
    {
        return in_array($this->insurer_api_status_id, app(abstract: PolicyIssuanceService::class)->getInsurerAPIStatuses(null, true));
    }

    /**
     * Get the API issuance status for this quote
     *
     * @return string|null
     */
    public function getApiIssuanceStatusAttribute()
    {
        return $this->api_issuance_status_id ? PolicyIssuanceEnum::getAPIIssuanceStatuses($this->api_issuance_status_id) : null;
    }

    /**
     * Get the insurer API status for this quote
     *
     * @return string|null
     */
    public function getInsurerApiStatusAttribute()
    {
        return $this->insurer_api_status_id ? app(PolicyIssuanceService::class)->getInsurerAPIStatuses($this->insurer_api_status_id) : null;
    }

    public function subSource()
    {
        return $this->belongsTo(Lookup::class, 'sub_source_id');
    }

    public function subSourceOption()
    {
        return $this->belongsTo(Lookup::class, 'sub_source_options_id');
    }

    public function branch()
    {
        return $this->hasOne(Branch::class, 'id', 'branch_id');
    }

    public function branchOverride()
    {
        return $this->morphOne(BranchOverride::class, 'quote_request');
    }

    public function amlAutomation()
    {
        return $this->hasOne(AmlAutomation::class, 'code', 'code');
    }

    public function isAutomationCompleted()
    {
        return $this->policyIssuance?->status === PolicyIssuanceEnum::COMPLETED_STATUS;
    }

    public function dttRevivalsAsParent(): HasMany
    {
        return $this->hasMany(DttRevival::class, 'previous_quote_id');
    }

    public function scopeWhereShortRevivalNotConverted(Builder $query): Builder
    {
        return $query->whereDoesntHave('dttRevivalsAsParent', function ($q): void {
            $q->join('personal_quotes as revival_child', 'revival_child.id', '=', 'dtt_revivals.quote_id')
                ->where('revival_child.quote_status_id', QuoteStatusEnum::PolicyBooked);
        });
    }

}
