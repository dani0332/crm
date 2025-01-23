<?php

namespace App\Models;

use App\Enums\ApplicationStorageEnums;
use App\Enums\FilterTypes;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypeId;
use App\Events\QuoteEmailUpdated;
use App\Traits\FilterCriteria;
use App\Traits\QuoteModelTrait;
use Auth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use OwenIt\Auditing\Auditable;

class CarQuote extends BaseModel
{
    use Auditable, FilterCriteria, HasFactory, QuoteModelTrait;

    protected $table = 'car_quote_request';
    protected $casts = [
        'dob' => 'datetime',
    ];
    protected $guarded = [];
    public $filterables = [
        'code' => FilterTypes::EXACT,
        'first_name' => FilterTypes::FREE,
        'last_name' => FilterTypes::FREE,
        'email' => FilterTypes::EXACT,
        'mobile_no' => FilterTypes::EXACT,
        'created_at' => FilterTypes::DATE_BETWEEN,
        'payment_status_id' => FilterTypes::IN,
        'is_ecommerce' => FilterTypes::EXACT,
        'quote_status_id' => FilterTypes::IN,
        'tier_id' => FilterTypes::IN,
        'vehicle_type_id' => FilterTypes::EXACT,
        'car_type_insurance_id' => FilterTypes::EXACT,
        'renewal_batch' => FilterTypes::EXACT,
        'policy_expiry_date' => FilterTypes::DATE_BETWEEN,
        'policy_number' => FilterTypes::NULL_CHECK,
        'source' => FilterTypes::EXACT,
        'advisor_id' => FilterTypes::IN,
        'created_at' => FilterTypes::DATE,
        'previous_quote_policy_number' => FilterTypes::EXACT,
        'renewal_batch' => FilterTypes::EXACT,
        'mobile_no' => FilterTypes::EXACT,
        'quote_batch_id' => FilterTypes::IN,
    ];
    protected $dispatchesEvents = [
        'updated' => QuoteEmailUpdated::class,
    ];

    public function getForeignKey()
    {
        return 'car_quote_request_id';
    }

    protected static function booted()
    {
        static::updating(function ($model) {
            $skipBookingDateUpdateForNonCPD = true;
            if (isset(request()->sendUpdateId)) {
                $carQuote = new CarQuote;
                $endorsmentDetails = $carQuote->isCPDEndorsment(request()->sendUpdateId);
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
    public function getFullNameAttribute()
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function fullName()
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function uaeLicenseHeldFor()
    {
        return $this->belongsTo(UAELicenseHeldFor::class, 'uae_license_held_for_id');
    }

    public function uaeLicenseHeldForBackHome()
    {
        return $this->hasOne(UAELicenseHeldFor::class, 'id', 'back_home_license_held_for_id');
    }

    public function carMake()
    {
        return $this->belongsTo(CarMake::class, 'car_make_id')->select(['id', 'code', 'text']);
    }

    public function carModel()
    {
        return $this->belongsTo(CarModel::class, 'car_model_id')->select(['id', 'code', 'text']);
    }

    public function carModelDetail()
    {
        return $this->hasOne(CarModelDetail::class, 'id', 'car_model_detail_id');
    }

    public function emirate()
    {
        return $this->belongsTo(Emirate::class, 'emirate_of_registration_id');
    }

    public function claimHistory()
    {
        return $this->belongsTo(ClaimHistory::class, 'claim_history_id');
    }

    public function carTypeInsurance()
    {
        return $this->belongsTo(CarTypeInsurance::class, 'car_type_insurance_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function nationality()
    {
        return $this->belongsTo(Nationality::class, 'nationality_id')->select(['id', 'code', 'text']);
    }

    public function paymentStatus()
    {
        return $this->belongsTo(PaymentStatus::class, 'payment_status_id');
    }

    public function quoteStatus()
    {
        return $this->belongsTo(QuoteStatus::class, 'quote_status_id');
    }

    public function getCreatedAtAttribute($value)
    {
        $date_time_format = config('constants.DATETIME_DISPLAY_FORMAT');

        return Carbon::parse($value)->format($date_time_format);
    }

    public function getUpdatedAtAttribute($value)
    {
        $date_time_format = config('constants.DATETIME_DISPLAY_FORMAT');

        return Carbon::parse($value)->format($date_time_format);
    }

    /*****  NewRelationships so old should not effect */

    public function car_make_id()
    {
        return $this->hasOne(CarMake::class, 'id', 'car_make_id')->select(['id', 'code', 'text']);
    }

    public function carQuoteRequestDetail()
    {
        return $this->hasOne(CarQuoteRequestDetail::class, 'car_quote_request_id', 'id');
    }

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class, 'insurance_provider_id', 'id')->select(['id', 'text']);
    }

    public function car_model_id()
    {
        return $this->hasOne(CarModel::class, 'id', 'car_model_id')->select(['id', 'code', 'text']);
    }

    public function emirate_of_registration_id()
    {
        return $this->hasOne(Emirate::class, 'id', 'emirate_of_registration_id')->select(['id', 'code', 'text']);
    }

    public function claim_history_id()
    {
        return $this->hasOne(ClaimHistory::class, 'id', 'claim_history_id')->select(['id', 'code', 'text']);
    }

    public function car_type_insurance_id()
    {
        return $this->hasOne(CarTypeInsurance::class, 'id', 'car_type_insurance_id')->select(['id', 'text']);
    }

    public function uae_license_held_for_id()
    {
        return $this->hasOne(UAELicenseHeldFor::class, 'id', 'uae_license_held_for_id')->select(['id', 'code', 'text']);
    }

    public function customer_id()
    {
        return $this->belongsTo(Customer::class);
    }

    public function nationality_id()
    {
        return $this->hasOne(Nationality::class, 'id', 'nationality_id')->select(['id', 'code', 'text']);
    }

    public function payment_status_id()
    {
        return $this->hasOne(PaymentStatus::class, 'id', 'payment_status_id');
    }

    public function plan_id()
    {
        return $this->hasOne(CarPlan::class, 'id', 'plan_id');
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    public function embeddedTransactions()
    {
        return $this->morphMany(EmbeddedTransaction::class, 'quote_request');
    }

    public function plan()
    {
        return $this->belongsTo(CarPlan::class, 'plan_id');
    }

    public function kyc_status_id()
    {
        return $this->hasOne(KycStatus::class, 'id', 'kyc_status_id');
    }

    public function quote_status_id()
    {
        return $this->hasOne(QuoteStatus::class, 'id', 'quote_status_id');
    }

    public function vehicle_detail_id()
    {
        return $this->hasOne(VehicleDetailCarQuote::class, 'car_quote_id', 'id');
    }

    public function vehicleType()
    {
        return $this->hasOne(VehicleType::class, 'id', 'vehicle_type_id')->select(['id', 'text']);
    }

    public function payment_detail()
    {
        return $this->hasOne(CarQuotePayment::class, 'car_quote_id', 'id');
    }

    public function insurance_coverage()
    {
        return $this->hasOne(CarQuoteInsuranceCoverage::class, 'car_quote_id', 'id');
    }

    public function car_quote_kyc()
    {
        return $this->hasOne(CarQuoteKyc::class, 'car_quote_id', 'id');
    }

    public function pa_id()
    {
        return $this->hasOne(User::class, 'id', 'pa_id')->select(['id', 'email', 'name']);
    }

    public function payment_id()
    {
        return $this->hasOne(User::class, 'id', 'payment_id')->select(['id', 'email', 'name']);
    }

    public function advisor_id()
    {
        return $this->hasOne(User::class, 'id', 'advisor_id')->select(['id', 'email', 'name']);
    }

    public function advisor()
    {
        return $this->hasOne(User::class, 'id', 'advisor_id')->select(['id', 'email', 'name', 'mobile_no', 'landline_no', 'profile_photo_path', 'calendar_link']);
    }

    public function batch()
    {
        return $this->hasOne(QuoteBatches::class, 'id', 'quote_batch_id')->select(['id', 'name', 'start_date', 'end_date']);
    }

    public function oe_id()
    {
        return $this->hasOne(User::class, 'id', 'oe_id')->select(['id', 'email', 'name']);
    }

    public function tier()
    {
        return $this->hasOne(Tier::class, 'id', 'tier_id')->select(['id', 'name', 'min_price', 'max_price', 'cost_per_lead']);
    }

    public function quoteViewCount()
    {
        return $this->hasOne(QuoteViewCount::class, 'quote_id', 'id')->where('quote_type_id', 1);
    }

    public function updatedBy()
    {
        return $this->hasOne(User::class, 'email', 'updated_by')->select(['id', 'email', 'name']);
    }

    public function previousAdvisor()
    {
        return $this->hasOne(User::class, 'id', 'previous_advisor_id')->select(['id', 'email', 'name']);
    }

    public function customerMembers()
    {
        return $this->morphMany(CustomerMembers::class, 'quote');
    }

    public function sageApiLogs()
    {
        return $this->morphMany(SageApiLog::class, 'section');
    }

    public function scopeRelationWhere($query, $isGetList, $filters)
    {
        if (Auth::user()->hasRole('pa') && $isGetList) {
            $query->select(['car_quote_request.id', 'code', 'first_name', 'last_name', 'car_quote_request.updated_at', 'car_quote_request.created_at', 'pa_id', 'kyc_status_id', 'quote_status_id', 'aml_status', 'payment_id', 'car_value', 'currently_insured_with', 'car_type_insurance_id', 'plan_id']);
            $query->join('car_quote_insurance_coverage', 'car_quote_insurance_coverage.car_quote_id', '=', 'car_quote_request.id');
        }
    }

    public function scopeFilterBySegment($query)
    {
        $segmentFilter = request()->input('segment_filter');
        self::applySegmentFilter($query, $segmentFilter, 'car_quote_request', QuoteTypeId::Car);
    }

    /*****  NewRelationships so old should not effect */

    public function relations()
    {
        if ($this->isGetList) {
            return ['pa_id', 'payment_id', 'quote_status_id', 'plan_id'];
        } else {
            return ['car_type_insurance_id', 'payment_detail', 'quote_status_id', 'kyc_status_id', 'insurance_coverage.insurance_company_id', 'insurance_coverage.insurance_plan_id', 'insurance_coverage.vehicle_type_id', 'uae_license_held_for_id', 'car_make_id', 'car_model_id', 'emirate_of_registration_id', 'claim_history_id',  'nationality_id', 'vehicle_detail_id', 'pa_id', 'car_quote_kyc',  'plan_id', 'plan_id.provider_id'];
        }
    }

    public $access = [

        'write' => ['advisor', 'oe'],
        'update' => ['advisor', 'payment', 'invoicing', 'pa', 'oe'],
        'delete' => ['advisor', 'oe'],
        'access' => [
            'pa' => ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'created_at', 'pa_id', 'payment_id'],
            'production_approval_manager' => ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'created_at', 'pa_id', 'payment_id'],
            'advisor' => ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'created_at', 'car_value', 'dob', 'nationality_id'],
            'oe' => ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'created_at', 'car_value', 'dob', 'nationality_id'],
            'admin' => ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'created_at', 'car_value'],
            'payment' => ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'created_at', 'payment_id'],
            'invoicing' => ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'created_at', 'invoicing'],
        ],
        'list' => [
            'pa' => ['id', 'code', 'first_name', 'last_name',  'created_at', 'pa_id', 'kyc_status_id', 'quote_status_id', 'aml_status', 'payment_id', 'car_value', 'currently_insured_with', 'car_type_insurance_id', 'plan_id'],
            'production_approval_manager' => ['id', 'code', 'first_name', 'last_name',  'created_at', 'pa_id', 'kyc_status_id', 'quote_status_id', 'aml_status', 'payment_id', 'car_value', 'currently_insured_with', 'car_type_insurance_id', 'plan_id'],
            'advisor' => ['id', 'code', 'first_name', 'last_name', 'updated_at', 'created_at', 'pa_id', 'kyc_status_id', 'quote_status_id', 'aml_status', 'payment_id', 'car_value', 'currently_insured_with', 'car_type_insurance_id', 'plan_id'],
            'oe' => ['id', 'code', 'first_name', 'last_name', 'updated_at', 'created_at', 'pa_id', 'kyc_status_id', 'quote_status_id', 'aml_status', 'payment_id', 'car_value', 'currently_insured_with', 'car_type_insurance_id', 'plan_id'],
            'admin' => ['id', 'code', 'first_name', 'last_name',  'created_at', 'kyc_status_id', 'quote_status_id', 'aml_status', 'payment_id', 'car_value', 'currently_insured_with', 'car_type_insurance_id', 'plan_id'],
            'payment' => ['id', 'code', 'first_name', 'last_name', 'pa_id',  'created_at', 'kyc_status_id', 'quote_status_id', 'aml_status', 'payment_id', 'car_value', 'currently_insured_with', 'car_type_insurance_id', 'plan_id'],
            'invoicing' => ['id', 'code', 'first_name', 'last_name', 'pa_id',  'created_at', 'kyc_status_id', 'quote_status_id', 'aml_status', 'payment_id', 'car_value', 'currently_insured_with', 'car_type_insurance_id', 'invoicing', 'plan_id'],
        ],
        'detail' => [
            'pa' => ['id', 'code', 'dob', 'first_name', 'last_name', 'email', 'mobile_no', 'Year_of_manufacture', 'kyc_status_id', 'quote_status_id', 'created_at', 'car_make_id', 'car_model_id', 'car_value', 'emirate_of_registration_id', 'claim_history_id',  'nationality_id', 'uae_license_held_for_id', 'pa_id', 'aml_status', 'payment_id', 'currently_insured_with', 'car_type_insurance_id', 'plan_id'],
            'production_approval_manager' => ['id', 'code', 'dob', 'first_name', 'last_name', 'email', 'mobile_no', 'Year_of_manufacture', 'kyc_status_id', 'quote_status_id', 'created_at', 'car_make_id', 'car_model_id', 'car_value', 'emirate_of_registration_id', 'claim_history_id',  'nationality_id', 'uae_license_held_for_id', 'pa_id', 'aml_status', 'payment_id', 'currently_insured_with', 'car_type_insurance_id', 'plan_id'],
            'advisor' => ['id', 'code', 'dob', 'first_name', 'last_name', 'email', 'mobile_no', 'Year_of_manufacture', 'kyc_status_id', 'quote_status_id', 'created_at', 'car_make_id', 'car_model_id', 'car_value', 'emirate_of_registration_id', 'claim_history_id',  'nationality_id', 'uae_license_held_for_id', 'pa_id', 'aml_status', 'payment_id', 'currently_insured_with', 'car_type_insurance_id', 'plan_id'],
            'oe' => ['id', 'code', 'dob', 'first_name', 'last_name', 'email', 'mobile_no', 'Year_of_manufacture', 'kyc_status_id', 'quote_status_id', 'created_at', 'car_make_id', 'car_model_id', 'car_value', 'emirate_of_registration_id', 'claim_history_id',  'nationality_id', 'uae_license_held_for_id', 'pa_id', 'aml_status', 'payment_id', 'currently_insured_with', 'car_type_insurance_id', 'plan_id'],
            'admin' => ['id', 'code', 'dob', 'first_name', 'last_name', 'email', 'mobile_no', 'Year_of_manufacture', 'kyc_status_id', 'quote_status_id', 'created_at', 'car_make_id', 'car_model_id', 'car_value', 'emirate_of_registration_id', 'claim_history_id',  'nationality_id', 'uae_license_held_for_id', 'pa_id', 'aml_status', 'payment_id', 'currently_insured_with', 'car_type_insurance_id', 'plan_id'],
            'payment' => ['id', 'code', 'dob', 'first_name', 'last_name', 'email', 'mobile_no', 'Year_of_manufacture', 'kyc_status_id', 'quote_status_id', 'created_at', 'car_make_id', 'car_model_id', 'car_value', 'emirate_of_registration_id', 'claim_history_id',  'nationality_id', 'uae_license_held_for_id', 'pa_id', 'aml_status', 'payment_id', 'currently_insured_with', 'car_type_insurance_id', 'plan_id'],
            'invoicing' => ['id', 'code', 'dob', 'first_name', 'last_name', 'email', 'mobile_no', 'Year_of_manufacture', 'kyc_status_id', 'quote_status_id', 'created_at', 'car_make_id', 'car_model_id', 'car_value', 'emirate_of_registration_id', 'claim_history_id',  'nationality_id', 'uae_license_held_for_id', 'pa_id', 'aml_status', 'payment_id', 'currently_insured_with', 'car_type_insurance_id', 'plan_id'],
        ],
    ];

    public function saveForm($request, $update = false)
    {
        if (Auth::user()->hasRole('pa') && $request->has('action')) {
            $request->request->add(['pa_id' => Auth::user()->id]);

            return parent::saveForm($request, true);
        } elseif (Auth::user()->hasRole('payment') && $request->has('action')) {
            $request->request->add(['payment_id' => Auth::user()->id]);

            return parent::saveForm($request, true);
        } elseif (Auth::user()->hasRole('invoicing') && $request->has('action')) {
            $request->request->add(['invoicing' => Auth::user()->id]);

            return parent::saveForm($request, true);
        } else {
            return parent::saveForm($request, $update);
        }
    }

    public function documents()
    {
        return $this->morphMany(QuoteDocument::class, 'quote_documentable');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function carLostQuoteLogs()
    {
        return $this->hasMany(CarLostQuoteLog::class, 'car_quote_request_id');
    }

    public function carLostQuoteLog()
    {
        return $this->hasOne(CarLostQuoteLog::class, 'car_quote_request_id')->latestOfMany();
    }

    public function quoteRequestEntityMapping()
    {
        return $this->hasOne(QuoteRequestEntityMapping::class, 'quote_request_id')
            ->where('quote_type_id', QuoteTypeId::Car);
    }

    public function activities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Activities::class, 'quote_request_id')
            ->where('quote_type_id', QuoteTypeId::Car);
    }

    public function duplicateInquiryLog(): MorphMany
    {
        return $this->morphMany(DuplicateInquiryLog::class, 'loggable');
    }

    public function policyWording()
    {
        return $this->hasMany(CarPlanPolicyWording::class, 'plan_id', 'plan_id');
    }

    // Get insurance provider for plan details section
    public function insuranceProviderDetails()
    {
        return $this->belongsTo(InsuranceProvider::class, 'insurance_provider_id', 'id');
    }

    public function hasExemptedSource()
    {
        // Check if Dubai Now exclusion should be applied
        $shouldIncludeDubaiNow = getAppStorageValueByKey(ApplicationStorageEnums::APPLY_DUBAI_NOW_EXCLUSION) == 1;

        // List of exempted lead sources
        $exemptedLeadSources = [LeadSourceEnum::IMCRM, LeadSourceEnum::INSLY, LeadSourceEnum::REVIVAL];

        // Add Dubai Now to exempted lead sources if $shouldIncludeDubaiNow is true
        if ($shouldIncludeDubaiNow) {
            $exemptedLeadSources[] = LeadSourceEnum::DUBAI_NOW;
        }

        return in_array($this->source, $exemptedLeadSources);
    }

    public function isRenewalTierEmailSent()
    {
        return $this->is_renewal_tier_email_sent == 1;
    }
}
