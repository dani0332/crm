<?php

namespace App\Models;

use App\Casts\EaModelCast;
use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\EmirateEnum;
use App\Enums\FilterTypes;
use App\Enums\GenericRequestEnum;
use App\Enums\HealthCoverForEnum;
use App\Enums\HealthQuoteDigitalSignatory;
use App\Enums\HealthQuoteUaePassApiStatus;
use App\Enums\HealthTeamType;
use App\Enums\LeadSourceEnum;
use App\Enums\LookupsEnum;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\TeamNameEnum;
use App\Events\QuoteEmailUpdated;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
use App\Services\LookupService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Traits\FilterCriteria;
use App\Traits\QuoteModelTrait;
use App\Traits\SpatieActivityLog;
use App\Traits\TransformsAuditables;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class HealthQuote extends Model implements AuditableContract
{
    use Auditable, FilterCriteria, HasFactory, QuoteModelTrait, SpatieActivityLog, TransformsAuditables;

    protected $casts = [
        'ea_model' => EaModelCast::class,
    ];
    protected $appends = [
        'insurer_aml_status_text', 'assignment_type_text', 'dob_formatted', 'previous_policy_expiry_date_formatted',
        'pc_qualified_formatted', 'has_pec_tag', 'signatory_text', 'uae_pass_api_status_text', 'is_migrated',
    ];
    protected $table = 'health_quote_request';
    protected $fillable = [];
    public $filterables = [
        'first_name' => FilterTypes::FREE,
        'last_name' => FilterTypes::FREE,
        'previous_quote_policy_number' => FilterTypes::EXACT,
        'policy_number' => FilterTypes::EXACT,
        'code' => FilterTypes::EXACT,
        'email' => FilterTypes::EXACT,
        'source' => FilterTypes::EXACT,
        'policy_expiry_date' => FilterTypes::DATE_BETWEEN,
        'mobile_no' => FilterTypes::EXACT,
        'created_at' => FilterTypes::DATE_BETWEEN,
    ];
    protected $guarded = [];
    protected $dispatchesEvents = [
        'updated' => QuoteEmailUpdated::class,
    ];
    protected array $auditRelationMap = [
        'marital_status_id' => ['relation' => 'maritalStatus', 'field' => 'text'],
        'cover_for_id' => ['relation' => 'healthCoverFor', 'field' => 'text'],
        'emirate_of_your_visa_id' => ['relation' => 'emirate', 'field' => 'text'],
        'customer_id' => ['relation' => 'customer', 'field' => 'email'],
        'nationality_id' => ['relation' => 'nationality', 'field' => 'text'],
        'payment_status_id' => ['relation' => 'paymentStatus', 'field' => 'text'],
        'quote_status_id' => ['relation' => 'quoteStatus', 'field' => 'text'],
        'advisor_id' => ['relation' => 'advisor', 'field' => 'email'],
        'wcu_id' => ['relation' => 'wcAdvisor', 'field' => 'email'],
        'lead_type_id' => ['relation' => 'healthLeadType', 'field' => 'text'],
        'salary_band_id' => ['relation' => 'salaryBand', 'field' => 'text'],
        'member_category_id' => ['relation' => 'memberCategory', 'field' => 'text'],
        'plan_id' => ['relation' => 'plan', 'field' => 'text'],
        'currently_insured_with_id' => ['relation' => 'currentlyInsured', 'field' => 'text'],
        'previous_advisor_id' => ['relation' => 'previousAdvisor', 'field' => 'email'],
        'renewal_batch_id' => ['relation' => 'renewalBatchModel', 'field' => 'name'],
        'insurance_provider_id' => ['relation' => 'insuranceProvider', 'field' => 'text'],
        'sub_source_id' => ['relation' => 'subSource', 'field' => 'text'],
        'sub_source_options_id' => ['relation' => 'subSourceOption', 'field' => 'text'],
        'support_user_id' => ['relation' => 'supportUser', 'field' => 'email'],
        'branch_id' => ['relation' => 'branch', 'field' => 'name'],
        'visa_category_id' => ['relation' => 'visaCategory', 'field' => 'text'],
        'pa_id' => ['relation' => 'pa', 'field' => 'email'],
        'health_plan_type_id' => ['relation' => 'healthPlanType', 'field' => 'text'],
        'health_plan_co_payment_id' => ['relation' => 'healthPlanCoPayment', 'field' => 'text'],
        'policy_issuance_status_id' => ['relation' => 'policyIssuanceStatus', 'field' => 'text'],
        'prefill_plan_id' => ['relation' => 'prefillPlan', 'field' => 'text'],
        'transaction_type_id' => ['relation' => 'transactionType', 'field' => 'text'],
        'quote_batch_id' => ['relation' => 'quoteBatch', 'field' => 'name'],
    ];

    protected static function booted()
    {
        static::updating(function ($model) {
            $skipBookingDateUpdateForNonCPD = true;
            if (isset(request()->sendUpdateId)) {
                $healthQuote = new HealthQuote;
                $endorsmentDetails = $healthQuote->isCPDEndorsment(request()->sendUpdateId);
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
        return $this->insurer_api_status_id ? app(PolicyIssuanceService::class)->getInsurerAPIStatuses($this->insurer_api_status_id) : null;
    }

    public function getAuditables()
    {
        return [
            'auditable_type' => self::class,
            'api_auditable_type' => 'App\Models\HealthQuoteRequest',
            'relations' => [
                [
                    'auditable_type' => CustomerMembers::class,
                    'api_auditable_type' => 'App\Models\HealthQuoteRequestMemberDetails',
                    'key' => 'quote_id',
                    'relation' => 'many',
                ],
            ],
        ];
    }
    public function previousQuote(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_quote_id');
    }

    public function emirate()
    {
        return $this->belongsTo(Emirate::class, 'emirate_of_your_visa_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function currentlyInsured()
    {
        return $this->hasOne(InsuranceProvider::class, 'id', 'currently_insured_with_id');
    }

    public function nationality()
    {
        return $this->belongsTo(Nationality::class, 'nationality_id');
    }

    public function paymentStatus()
    {
        return $this->belongsTo(PaymentStatus::class, 'payment_status_id');
    }

    public function quoteStatus()
    {
        return $this->belongsTo(QuoteStatus::class, 'quote_status_id');
    }

    public function healthCoverFor()
    {
        return $this->belongsTo(HealthCoverFor::class, 'cover_for_id');
    }

    public function maritalStatus()
    {
        return $this->belongsTo(MartialStatus::class, 'marital_status_id');
    }

    public function healthQuoteRequestDetail()
    {
        return $this->hasOne(HealthQuoteRequestDetail::class, 'health_quote_request_id', 'id');
    }

    public function currentProvider()
    {
        return $this->hasOne(InsuranceProvider::class, 'id', 'currently_insured_with_id');
    }

    public function memberDetails()
    {
        return $this->hasMany(HealthMemberDetail::class, 'id', 'primary_member_id');
    }

    public function memberCategory()
    {
        return $this->belongsTo(MemberCategory::class, 'member_category_id', 'id');
    }

    public function salaryBand()
    {
        return $this->belongsTo(SalaryBand::class, 'salary_band_id', 'id');
    }

    public function advisor()
    {
        return $this->hasOne(User::class, 'id', 'advisor_id');
    }

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class, 'insurance_provider_id', 'id')->select(['id', 'text', 'code']);
    }

    public function wcAdvisor()
    {
        return $this->hasOne(User::class, 'id', 'wcu_id');
    }

    public function supportUser()
    {
        return $this->belongsTo(User::class, 'support_user_id');
    }

    public function preQualificationAdvisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pq_advisor_id', 'id');
    }

    public function getFullNameAttribute()
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function documents()
    {
        return $this->morphMany(QuoteDocument::class, 'quote_documentable');
    }

    public function vehicleDriverDetail()
    {
        return $this->morphOne(VehicleDriverDetail::class, 'quoteable');
    }

    /**
     * @return HasMany
     */
    public function members()
    {
        return $this->morphMany(CustomerMembers::class, 'quote');
    }

    public function activeMembers()
    {
        return $this->members()->whereNull('deleted_at');
    }

    public function plan()
    {
        return $this->belongsTo(HealthPlan::class, 'plan_id');
    }

    public function healthQuotePlan()
    {
        return $this->hasOne(HealthQuotePlan::class, 'health_quote_request_id');
    }

    public function lostReason()
    {
        return $this->belongsTo(LostReasons::class, 'lost_reason_id');
    }

    public function healthLeadType()
    {
        return $this->belongsTo(HealthLeadType::class, 'lead_type_id');
    }

    public function quoteRequestEntityMapping()
    {
        return $this->hasOne(QuoteRequestEntityMapping::class, 'quote_request_id')
            ->where('quote_type_id', QuoteTypeId::Health);
    }

    public function customerMembers()
    {
        return $this->morphMany(CustomerMembers::class, 'quote');
    }

    public function sageApiLogs()
    {
        return $this->morphMany(SageApiLog::class, 'section');
    }
    public function activities(): HasMany
    {
        return $this->hasMany(Activities::class, 'quote_request_id')
            ->where('quote_type_id', QuoteTypeId::Health);
    }

    public function notes()
    {
        return $this->morphMany(QuoteNote::class, 'quote_noteable');
    }

    public function duplicateInquiryLog(): MorphMany
    {
        return $this->morphMany(DuplicateInquiryLog::class, 'loggable');
    }

    public static function getCustomerMemberName($id)
    {
        $customerMember = CustomerMembers::find($id);
        if ($customerMember) {
            if ($customerMember->first_name == null && $customerMember->last_name == null) {
                $quoteMemberCount = CustomerMembers::where([
                    'customer_type' => $customerMember->customer_type,
                    'first_name' => GenericRequestEnum::MEMBER,
                ])->count();
                $customerMember->first_name = GenericRequestEnum::MEMBER;
                $customerMember->last_name = (++$quoteMemberCount);
                $customerMember->save();
            }

            return $customerMember->first_name.' '.$customerMember->last_name;
        } else {
            $healthQuote = HealthQuote::find($id);
            if ($healthQuote) {
                return $healthQuote->first_name.' '.$healthQuote->last_name;
            }
        }

        return 'Price';
    }

    public function policyWording()
    {
        return $this->hasMany(HealthPlanPolicyWording::class, 'plan_id', 'plan_id');
    }

    public function isApplicationPending()
    {
        return $this->quote_status_id === QuoteStatusEnum::ApplicationPending;
    }

    public function isApplyNowEmailSent()
    {
        return ! is_null($this->apply_now_email_sent_at);
    }

    public function getCurrentPlan()
    {
        $payload = $this->healthQuotePlan?->payload;
        if ($payload && property_exists($payload, 'plans')) {
            return collect($payload->plans)->filter(fn ($plan) => $plan && $plan->id === $this->plan_id)->first();
        }

        return null;
    }

    public function isValueLead()
    {
        return $this->health_team_type === HealthTeamType::RM_SPEED || $this->notional_team === TeamNameEnum::RM_SPEED;
    }

    public function isVolumeLead()
    {
        return $this->health_team_type === HealthTeamType::EBP || $this->notional_team === TeamNameEnum::EBP;
    }

    public function previousAdvisor()
    {
        return $this->belongsTo(User::class, 'previous_advisor_id');
    }

    public function leadGenerator()
    {
        return $this->hasOne(User::class, 'id', 'lead_generator_id')->select(['id', 'email', 'name']);
    }

    public function expertAdvisor()
    {
        return $this->hasOne(User::class, 'id', 'expert_advisor_id')->select(['id', 'email', 'name', 'mobile_no']);
    }

    public function dependentMembers()
    {
        return $this->hasMany(HealthMemberDetail::class, 'health_quote_request_id', 'id')
            ->where('is_primary', false);
    }

    public function renewalBatchModel()
    {
        return $this->belongsTo(RenewalBatch::class, 'renewal_batch_id');
    }

    // Reminder:: this relation is being used for currently insured customer
    public function insured()
    {
        return $this->belongsTo(Customer::class, 'currently_insured_id');
    }

    public function entity()
    {
        return $this->belongsTo(Entity::class, 'entity_id');
    }

    public function planProvider()
    {
        return $this->belongsTo(InsuranceProvider::class, 'plan_provider_id');
    }

    public function quotePlan()
    {
        return $this->hasOne(HealthQuotePlan::class, 'id', 'plan_id');
    }

    public function scopeFilterBySegment($query)
    {
        $segmentFilter = request()->input('segment_filter');
        self::applySegmentFilter($query, $segmentFilter, 'health_quote_request', QuoteTypeId::Health);
    }

    public function getCarTeamsAttribute()
    {
        if ($this->advisor_id) {

            $carTeam = $this->getProductByName(quoteTypeCode::Car);

            $query = DB::table(DB::raw("(SELECT GROUP_CONCAT(DISTINCT t1.name SEPARATOR ', ') AS CarTeams
                        FROM teams t1
                            WHERE t1.parent_team_id = $carTeam->id
                            AND t1.name IN (
                                SELECT t2.name
                                FROM teams t2
                                JOIN user_team ut2 ON t2.id = ut2.team_id
                                WHERE ut2.user_id = ".$this->advisor_id.')
                        ) AS CarTeams'))
                ->select(DB::raw('CarTeams'))
                ->first();

            return $query ? $query->CarTeams : null;
        } else {
            return 'N/A';
        }
    }

    // Reminder:: This relationship is used when we create child lead through CIR - only active insured record will be cloned
    public function customerInsured()
    {
        return $this->hasOne(CustomerInsured::class, 'quote_request_id', 'id')
            ->where('quote_type_id', QuoteTypeId::Health)
            ->active();
    }

    // Reminder::Get the active insured record for this quote
    public function latestInsured(): HasOneThrough
    {
        return $this->hasOneThrough(
            Insured::class,
            CustomerInsured::class,
            'quote_request_id', // customer_insured.quote_request_id
            'id', // insured.id
            'id', // health_quote_requests.id
            'insured_id' // customer_insured.insured_id
        )
            ->where('customer_insured.quote_type_id', QuoteTypeId::Health)
            ->where('customer_insured.is_active', true);
    }

    public function amlLogs()
    {
        return $this->hasMany(KycLog::class, 'quote_request_id', 'id')
            ->where('quote_type_id', QuoteTypeId::Health)->withTrashed();
    }

    /******************************* Quote Status Logs Related Methods Below *******************************/
    /**
     * Get all quote status logs for this model
     */
    public function quoteStatusLogs(): HasMany
    {
        return $this->hasMany(QuoteStatusLog::class, 'quote_request_id')->where('quote_type_id', QuoteTypeId::Health);
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
     * Check if quote can be updated to transaction approved status
     * Only allowed if quote has both payment link sent and initiated status in history
     */
    public function canUpdateToTransactionApproved(): bool
    {
        return $this->hasPaymentLinkHistory();
    }

    /******************************* Payments Related Methods Below *******************************/
    /**
     * Get all payments for this model
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'paymentable');
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
    public function ftcEmailLogs()
    {
        return $this->morphMany(FtcEmailLog::class, 'quote_trackable');
    }

    public function personalQuote()
    {
        return $this->belongsTo(PersonalQuote::class, 'id', 'quote_id')->where('quote_type_id', QuoteTypeId::Health);
    }

    public function isAUHLead(bool $shouldCheckSource = true)
    {
        return $this->emirate_of_your_visa_id === EmirateEnum::ABU_DHABI && ($shouldCheckSource ? $this->source === LeadSourceEnum::IMCRM : true);
    }

    public function isPECLead(): bool
    {
        return ! empty($this->has_pec_tag);
    }

    public function isSIC1(): bool
    {
        return ! $this->health_plan_type_id;
    }

    public function isSIC2(): bool
    {
        $sicConfig = SICConfig::where('quote_type_id', QuoteTypeId::Health)->first();

        if (! $sicConfig) {
            return false;
        }

        // Only use age-based classification when DOB is present; null DOB must not be treated as age 0
        if (! empty($this->dob)) {
            $age = Carbon::parse($this->dob)->age;
            LoggerService::info('Lead applicant age', ['age' => $age]);

            if ($age >= $sicConfig->min_age && $age <= $sicConfig->max_age) {
                return true;
            }
        }

        if (! empty($this->price_starting_from)) {
            if ($this->price_starting_from < $sicConfig->price_starting_from) {
                return true;
            }
        }

        return false;
    }
    public function hasAnyMemberAgeSixtyOrAbove(): bool
    {
        foreach ($this->activeMembers as $member) {
            if (empty($member->dob)) {
                continue;
            }

            if (Carbon::parse($member->dob)->age >= 60) {
                return true;
            }
        }

        return false;
    }

    public function isSourceApplicable(): bool
    {
        LoggerService::info("isSourceApplicable check for lead source {$this->source}", ['source' => $this->source]);
        $appStorageValue = ApplicationStorageService::getValueByKeyName(ApplicationStorageEnums::LEAD_SOURCE_ECOMMERCE);
        LoggerService::info("App storage Value for lead source {$appStorageValue}", ['appStorageValue' => $appStorageValue]);

        $host = parse_url($this->source, PHP_URL_HOST);
        $domains = explode(',', $appStorageValue);

        if (! in_array($host, $domains) && $this->source != LeadSourceEnum::INSURANCE_WALLET) {
            return false;
        }

        return true;
    }

    public function hasPecTag(): Attribute
    {
        return Attribute::make(
            get: function () {
                return ! empty($this->pec_marked_at);
            }
        );
    }

    public function signatoryText(): Attribute
    {
        return Attribute::make(
            get: fn () => HealthQuoteDigitalSignatory::displayLabel($this->digital_signatory),
        );
    }

    public function uaePassApiStatusText(): Attribute
    {
        return Attribute::make(
            get: fn () => HealthQuoteUaePassApiStatus::displayLabel($this->uae_pass_api_status),
        );
    }

    public function scopeHasPecTag($query)
    {
        $query->whereNotNull('pec_marked_at');
    }

    public function isLeadSourceRevivalOrInsuranceWallet()
    {
        return in_array($this->source, [LeadSourceEnum::REVIVAL, LeadSourceEnum::REVIVAL_REPLIED, LeadSourceEnum::REVIVAL_PAID, LeadSourceEnum::INSURANCE_WALLET]);
    }

    /**
     * Sub-source relationship
     */
    public function subSource()
    {
        return $this->belongsTo(Lookup::class, 'sub_source_id');
    }

    /**
     * Sub-source option relationship
     */
    public function subSourceOption()
    {
        return $this->belongsTo(Lookup::class, 'sub_source_options_id');
    }

    public function branch()
    {
        return $this->hasOne(Branch::class, 'id', 'branch_id');
    }

    public function insurerRequestResponses()
    {
        return $this->hasMany(HealthInsurerRequestResponse::class, 'quote_uuid', 'uuid');
    }

    public function insurerGenerateQuoteRequestResponse()
    {
        return $this->hasOne(HealthInsurerRequestResponse::class, 'quote_uuid', 'uuid')->where(['execution_method' => HealthInsurerRequestResponse::EXECUTION_METHOD_GENERATE_QUOTE, 'status' => 'passed'])->latest();
    }

    public function healthUmafResponse()
    {
        return $this->hasOne(HealthUMAFResponse::class, 'quote_uuid', 'uuid');
    }

    /**
     * Check if this quote is a Straight Through Processing (STP) case
     */
    public function isSTPCase(): bool
    {
        // Use data_get for safe nested access with default value
        return (bool) data_get($this->healthUmafResponse, 'stp_rating.is_stp', false);
    }

    public function isBookingFailed()
    {
        return $this->insurer_api_status_id === PolicyIssuanceEnum::PIA_BOOK_POLICY_API_FAILED_STATUS_ID;
    }

    public function isPolicyIssuanceFailed()
    {
        return in_array($this->insurer_api_status_id, app(PolicyIssuanceService::class)->getInsurerAPIStatuses(null, true));
    }

    public function policyIssuance()
    {
        return $this->morphOne(PolicyIssuance::class, 'model');
    }

    /**
     * ADNIC non-STP flag from MongoDB health-umaf-responses (stp_rating.is_non_stp).
     */
    public function hasAdnicPlan(): bool
    {
        $umaf = HealthUMAF::where('quote_uuid', $this->uuid)->first();
        if (! $umaf || ! $umaf?->isADNIC()) {
            return false;
        }

        return $umaf?->isADNIC() ? true : false;
    }

    public function visaCategory()
    {
        return $this->belongsTo(VisaCategory::class, 'visa_category_id');
    }

    public function policyHolderCategory()
    {
        return $this->belongsTo(Lookup::class, 'policy_holder_category_code', 'code')
            ->where('key', LookupsEnum::POLICY_HOLDER_CATEGORY->value);
    }

    public function genderLookup()
    {
        return $this->belongsTo(Lookup::class, 'gender', 'code')
            ->where('key', LookupsEnum::GENDER->value);
    }

    public function pa()
    {
        return $this->hasOne(User::class, 'id', 'pa_id');
    }

    public function healthPlanType()
    {
        return $this->belongsTo(HealthPlanType::class, 'health_plan_type_id');
    }

    public function healthPlanCoPayment()
    {
        return $this->belongsTo(HealthPlanCoPayment::class, 'health_plan_co_payment_id');
    }

    public function policyIssuanceStatus()
    {
        return $this->belongsTo(PolicyIssuanceStatus::class, 'policy_issuance_status_id');
    }

    public function prefillPlan()
    {
        return $this->belongsTo(HealthQuotePlan::class, 'prefill_plan_id');
    }

    public function transactionType()
    {
        return $this->belongsTo(Lookup::class, 'transaction_type_id', 'id');
    }

    public function quoteBatch()
    {
        return $this->belongsTo(QuoteBatches::class, 'quote_batch_id');
    }

    public function isEntity(): bool
    {
        if ($this->customer_id === null) {
            return false;
        }

        $insured = $this->latestInsured;

        return $insured !== null && $insured->customer_type === CustomerTypeEnum::Entity;
    }

    public static function customizeAuditTransformation($data): array
    {
        $lookupService = app(LookupService::class);

        $leadSource = $data['model']?->source;

        $insureCodeMapping = $lookupService->getHealthInsureOptions($leadSource)->pluck('text', 'code')->all();
        $policyHolderCodeMapping = $lookupService->getPolicyHolder($leadSource)->pluck('text', 'code')->all();

        $resolveText = function (array &$transformed, string $field, array $map): void {
            $code = $transformed[$field] ?? null;
            if ($code !== null) {
                $transformed[$field.'_text'] = $map[$code] ?? null;
            }
        };

        foreach (['transformedOld', 'transformedNew'] as $key) {
            $resolveText($data[$key], 'insure_code', $insureCodeMapping);
            $resolveText($data[$key], 'policy_holder_code', $policyHolderCodeMapping);
        }

        return $data;
    }

    public function isMigrated(): bool
    {
        return in_array($this->cover_for_id, [HealthCoverForEnum::INDIVIDUAL_AND_FAMILIES->value, HealthCoverForEnum::DOMESTIC_HELPER->value]);
    }

    public function getIsMigratedAttribute(): bool
    {
        return $this->isMigrated();
    }

    public function isPolicyholderIncluded(): bool
    {
        return $this->activeMembers
            ->where('is_policy_holder', true)
            ->where('is_insured', true)
            ->isNotEmpty();
    }
}
