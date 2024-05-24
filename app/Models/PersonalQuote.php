<?php

namespace App\Models;

use App\Enums\FilterTypes;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteSegmentEnum;
use App\Enums\QuoteTypeId;
use App\Traits\FilterCriteria;
use App\Traits\QuoteModelTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class PersonalQuote extends Model implements AuditableContract
{
    use Auditable, FilterCriteria, HasFactory, QuoteModelTrait;

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
        'is_ecommerce' => FilterTypes::EXACT,
        'previous_quote_policy_number' => FilterTypes::NULL_CHECK,
        'previous_quote_policy_number_text' => FilterTypes::EXACT,
        'advisor_id' => FilterTypes::IN,
        'policy_number' => FilterTypes::EXACT,
        'source' => FilterTypes::EXACT,
        'renewal_expiry_date' => FilterTypes::DATE_BETWEEN,
        'is_cold' => FilterTypes::EXACT,
        'stale_at' => FilterTypes::NULL_CHECK,
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function quoteStatus()
    {
        return $this->belongsTo(QuoteStatus::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function paymentStatus()
    {
        return $this->belongsTo(PaymentStatus::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function quoteDetail()
    {
        return $this->hasOne(PersonalQuoteDetail::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function advisor()
    {
        return $this->belongsTo(User::class, 'advisor_id')->select(['id', 'email', 'name', 'mobile_no', 'profile_photo_path']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function quoteType()
    {
        return $this->belongsTo(QuoteType::class);
    }

    /**
     * bike quote request relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function bikeQuote()
    {
        return $this->hasOne(BikeQuote::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function jetskiQuote()
    {
        return $this->hasOne(JetskiQuote::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function cycleQuote()
    {
        return $this->hasOne(CycleQuote::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function petQuote()
    {
        return $this->hasOne(PetQuote::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function yachtQuote()
    {
        return $this->hasOne(YachtQuote::class);
    }

    /**
     * @param  $date
     * @return string
     */
    public function getDobAttribute($value)
    {
        $date_time_format = config('constants.DATE_FORMAT');

        return Carbon::parse($value)->format($date_time_format);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class)->select(['id', 'name', 'email']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
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
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function documents()
    {
        return $this->morphMany(QuoteDocument::class, 'quote_documentable');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function payments()
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currentlyInsuredWith()
    {
        return $this->belongsTo(InsuranceProvider::class, 'currently_insured_with_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
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
            ->whereIn('quote_type_id', [QuoteTypeId::Cycle, QuoteTypeId::Bike, QuoteTypeId::Pet, QuoteTypeId::Yacht, QuoteTypeId::Jetski]);
    }

    public function activities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Activities::class, 'quote_request_id')
            ->whereIn('quote_type_id', [QuoteTypeId::Yacht, QuoteTypeId::Jetski, QuoteTypeId::Cycle, QuoteTypeId::Bike, QuoteTypeId::Pet]);
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
    public function scopeFilterBySegment($query, $segmentFilter, $quoteTypeCode)
    {
        self::applySegmentFilter($query, $segmentFilter, $quoteTypeCode);
    }

    public static function applySegmentFilter($query, $segmentFilter, $quoteTypeCode)
    {
        $user = auth()->user();
        if ($user->can(PermissionsEnum::SEGMENT_FILTER) && $segmentFilter) {
            $query->when($segmentFilter === QuoteSegmentEnum::SIC->value, function ($query) use ($quoteTypeCode) {
                $query->whereIn('personal_quotes.uuid', function ($query) use ($quoteTypeCode) {
                    $query->distinct()
                        ->select('quote_uuid')
                        ->from('quote_tags')
                        ->join('quote_type', 'quote_type.id', 'quote_tags.quote_type_id')
                        ->where('quote_tags.name', QuoteSegmentEnum::SIC->tag())
                        ->where('quote_type.code', $quoteTypeCode);
                });
            })->when($segmentFilter === QuoteSegmentEnum::NON_SIC->value, function ($query) use ($quoteTypeCode) {
                $query->whereNotIn('personal_quotes.uuid', function ($query) use ($quoteTypeCode) {
                    $query->distinct()
                        ->select('quote_uuid')
                        ->from('quote_tags')
                        ->join('quote_type', 'quote_type.id', 'quote_tags.quote_type_id')
                        ->where('quote_tags.name', QuoteSegmentEnum::SIC->tag())
                        ->where('quote_type.code', $quoteTypeCode);
                });
            });
        }
    }

    public function customerMembers()
    {
        return $this->morphMany(CustomerMembers::class, 'quote');
    }
}
