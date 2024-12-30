<?php

namespace App\Models;

use App\Enums\FilterTypes;
use App\Enums\QuoteTypeId;
use App\Events\QuoteEmailUpdated;
use App\Traits\FilterCriteria;
use App\Traits\QuoteModelTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
            'auditable_type' => self::class,
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
}
