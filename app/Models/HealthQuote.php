<?php

namespace App\Models;

use App\Enums\FilterTypes;
use App\Enums\GenericRequestEnum;
use App\Enums\HealthTeamType;
use App\Enums\PaymentMethodsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Events\QuoteEmailUpdated;
use App\Traits\FilterCriteria;
use App\Traits\QuoteModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class HealthQuote extends Model implements AuditableContract
{
    use Auditable, FilterCriteria, HasFactory, QuoteModelTrait;

    protected $appends = ['insurer_aml_status_text', 'assignment_type_text', 'dob_formatted', 'previous_policy_expiry_date_formatted', 'pc_qualified_formatted'];
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

    public function getAuditables()
    {
        return [
            'auditable_type' => self::class,
        ];
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

    public function getFullNameAttribute()
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function documents()
    {
        return $this->morphMany(QuoteDocument::class, 'quote_documentable');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function members()
    {
        return $this->morphMany(CustomerMembers::class, 'quote');
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
    public function activities(): \Illuminate\Database\Eloquent\Relations\HasMany
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
        return $this->health_team_type === HealthTeamType::RM_SPEED;
    }

    public function isVolumeLead()
    {
        return $this->health_team_type === HealthTeamType::EBP;
    }

    public function previousAdvisor()
    {
        return $this->belongsTo(User::class, 'previous_advisor_id');
    }

    public function dependentMembers()
    {
        return $this->hasMany(HealthMemberDetail::class, 'health_quote_request_id', 'id')
            ->where('is_primary', false);
    }

    public function renewalBatch()
    {
        return $this->belongsTo(RenewalBatch::class, 'renewal_batch_id');
    }

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

    // TODO:: Need to verify this function
    public function insuredDetails()
    {
        return $this->hasOneThrough(
            Insured::class,
            CustomerInsured::class,
            'quote_request_id', // Foreign key on customer_insured
            'id',               // Foreign key on insured
            'id',               // Local key on health_quote_requests
            'insured_id'        // Local key on customer_insured
        )->where('quote_type_id', QuoteTypeId::Health);
    }

    // Get the latest/most recent insured record for this quote
    public function latestInsured(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(
            Insured::class,
            CustomerInsured::class,
            'quote_request_id', // customer_insured.quote_request_id
            'id', // insured.id
            'id', // health_quote_requests.id
            'insured_id' // customer_insured.insured_id
        )->where('customer_insured.quote_type_id', QuoteTypeId::Health)
            ->latest('customer_insured.updated_at');
    }

    /******************************* Quote Status Logs Related Methods Below *******************************/
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
     * @return \Illuminate\Database\Eloquent\Collection
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
     * @return \App\Models\Payment|null
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
     * @return \Illuminate\Database\Eloquent\Collection
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
}
