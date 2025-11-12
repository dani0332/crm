<?php

namespace App\Models;

use App\Enums\FilterTypes;
use App\Enums\PaymentMethodsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Events\QuoteEmailUpdated;
use App\Traits\FilterCriteria;
use App\Traits\QuoteModelTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class LifeQuote extends Model implements AuditableContract
{
    use Auditable, FilterCriteria, HasFactory, QuoteModelTrait;

    protected $table = 'life_quote_request';
    protected $guarded = [];
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
        'advisor_id' => FilterTypes::IN,
        'renewal_batch_id' => FilterTypes::IN,
        'source' => FilterTypes::EXACT,
        'policy_expiry_date' => FilterTypes::DATE_BETWEEN,
        'previous_quote_policy_number' => FilterTypes::NULL_CHECK,
        'previous_quote_policy_number_text' => FilterTypes::EXACT,
    ];
    protected $dispatchesEvents = [
        'updated' => QuoteEmailUpdated::class,
    ];
    public $allowedColumns = ['others_info', 'is_smoker', 'sum_insured_value', 'sum_insured_currency_id', 'marital_status_id', 'purpose_of_insurance_id', 'children_id', 'tenure_of_insurance_id', 'number_of_years_id', 'height', 'weight', 'bmi', 'age', 'lang', 'uuid'];
    protected $appends = [
        'pc_qualified_formatted',
    ];

    protected static function booted()
    {
        static::updating(function ($model) {
            $skipBookingDateUpdateForNonCPD = true;
            if (isset(request()->sendUpdateId)) {
                $lifeQuote = new LifeQuote;
                $endorsmentDetails = $lifeQuote->isCPDEndorsment(request()->sendUpdateId);
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
            'auditable_type' => PersonalQuote::class,
            'relations' => [
                ['auditable_type' => PersonalQuoteDetail::class, 'key' => 'personal_quote_id'],
                ['auditable_type' => self::class, 'key' => 'personal_quote_id'],
            ],
        ];
    }
    public function getDobAttribute($value)
    {
        return Carbon::parse($value)->format(config('constants.DATE_FORMAT_ONLY'));
    }

    public function quoteStatus()
    {
        return $this->belongsTo(QuoteStatus::class, 'quote_status_id');
    }

    public function lifeQuoteRequestDetail()
    {
        return $this->hasOne(LifeQuoteRequestDetail::class, 'life_quote_request_id', 'id');
    }

    public function advisor()
    {
        return $this->belongsTo(User::class)->select(['id', 'email', 'name', 'mobile_no', 'landline_no', 'profile_photo_path', 'calendar_link']);
    }
    public function previousAdvisor()
    {
        return $this->belongsTo(User::class, 'previous_advisor_id', 'id');
    }
    public function nationality()
    {
        return $this->belongsTo(Nationality::class);
    }

    public function purposeOfInsurance()
    {
        return $this->belongsTo(LifePurposeOfInsurance::class, 'purpose_of_insurance_id', 'id');
    }

    public function children()
    {
        return $this->belongsTo(LifeChildren::class, 'children_id', 'id');
    }

    public function currency()
    {
        return $this->belongsTo(CurrencyType::class, 'sum_insured_currency_id');
    }

    public function insuranceTenure()
    {
        return $this->belongsTo(LifeInsuranceTenure::class, 'tenure_of_insurance_id');
    }

    public function numberOfYears()
    {
        return $this->belongsTo(LifeNumberOfYears::class, 'number_of_years_id');
    }

    public function maritalStatus()
    {
        return $this->belongsTo(MartialStatus::class, 'marital_status_id');
    }

    public function paymentStatus()
    {
        return $this->belongsTo(PaymentStatus::class, 'payment_status_id');
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function transactionType()
    {
        return $this->belongsTo(Lookup::class, 'transaction_type_id', 'id');
    }

    public function quoteRequestEntityMapping()
    {
        return $this->hasOne(QuoteRequestEntityMapping::class, 'quote_request_id')
            ->where('quote_type_id', QuoteTypeId::Life);
    }

    public function documents()
    {
        return $this->morphMany(QuoteDocument::class, 'quote_documentable');
    }

    public function activities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Activities::class, 'quote_request_id')
            ->where('quote_type_id', QuoteTypeId::Life);
    }

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class, 'insurance_provider_id', 'id');
    }

    public function sageApiLogs()
    {
        return $this->morphMany(SageApiLog::class, 'section');
    }

    public function customerMembers()
    {
        return $this->morphMany(CustomerMembers::class, 'quote');
    }

    public function quoteDetail()
    {
        return $this->hasOne(LifeQuoteRequestDetail::class);
    }

    public function renewalBatchModel()
    {
        return $this->belongsTo(RenewalBatch::class, 'renewal_batch_id');
    }

    public function notes()
    {
        return $this->morphMany(QuoteNote::class, 'quote_noteable');
    }

    public function allowedColumns()
    {
        return $this->allowedColumns;
    }

    public function customerInsured()
    {
        return $this->hasOne(CustomerInsured::class, 'quote_request_id', 'id')
            ->where('quote_type_id', QuoteTypeId::Life);
    }

    // Get all insured records for this quote (multiple AML screenings)
    public function insureds(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            Insured::class,
            CustomerInsured::class,
            'quote_request_id', // customer_insured.quote_request_id
            'id', // insured.id
            'id', // personal_quotes.id
            'insured_id' // customer_insured.insured_id
        );
    }

    // Get the latest/most recent insured record for this quote
    public function latestInsured(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(
            Insured::class,
            CustomerInsured::class,
            'quote_request_id', // customer_insured.quote_request_id
            'id', // insured.id
            'id', // personal_quotes.id
            'insured_id' // customer_insured.insured_id
        )->latest('customer_insured.updated_at');
    }

    public function amlLogs()
    {
        return $this->hasMany(KycLog::class, 'quote_request_id', 'id')
            ->where('quote_type_id', QuoteTypeId::Life)->withTrashed();
    }

    public function sumInsuredCurrency()
    {
        return $this->belongsTo(CurrencyType::class, 'sum_insured_currency_id');
    }

    public function policySumAssuredCurrency()
    {
        return $this->belongsTo(CurrencyType::class, 'policy_sum_assured_currency_id');
    }

    public function scopeFilterBySegment($query, $alias = 'lqr')
    {
        $segmentFilter = request()->input('segment_filter');
        self::applySegmentFilter($query, $segmentFilter, $alias, QuoteTypeId::Life);
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
    public function ftcEmailLogs(): MorphMany
    {
        return $this->morphMany(FtcEmailLog::class, 'quote_trackable');
    }

    public function personalQuote()
    {
        return $this->belongsTo(PersonalQuote::class, 'id', 'quote_id')->where('quote_type_id', QuoteTypeId::Life);
    }
}
