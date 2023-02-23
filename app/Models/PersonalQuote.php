<?php

namespace App\Models;

use App\Enums\FilterTypes;
use App\Traits\FilterCriteria;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class PersonalQuote extends Model
{
    use HasFactory, FilterCriteria;

    protected $appends = ['dob_formatted'];

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
        'previous_quote_policy_number' => FilterTypes::NULL_CHECK
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function quoteStatus()
    {
        return $this->belongsTo(QuoteStatus::class);
    }

    /**
     * @return void
     */
    public function quoteDetail()
    {
        return $this->hasOne(PersonalQuoteDetail::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function advisor()
    {
        return $this->belongsTo(User::class)->select(['id', 'email', 'name']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function quoteType()
    {
        return $this->belongsTo(QuoteType::class);
    }

    /**
     * bike quote request relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function bikeQuote()
    {
        return $this->hasOne(BikeQuote::class);
    }

    /**
     * @param $date
     * @return string
     */
    public function getCreatedAtAttribute($date)
    {
        if(!empty($date)) return Carbon::parse($date)->timezone(config('app.timezone'))->format(Config::get('constants.datetime_format'));
    }

    /**
     * @param $date
     * @return string
     */
    public function getUpdatedAtAttribute($date)
    {
        if(!empty($date)) return Carbon::parse($date)->timezone(config('app.timezone'))->format(Config::get('constants.datetime_format'));
    }

    /**
     * @param $date
     * @return string
     */
    public function getDobFormattedAttribute()
    {
        return $this->attributes['dob_formatted'] = $this->asDateTime($this->dob)->timezone(config('app.timezone'))->format(Config::get('constants.DATE_FORMAT'));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy() {
        return $this->belongsTo(User::class)->select(['id', 'name', 'email']);
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy() {
        return $this->belongsTo(User::class)->select(['id', 'name', 'email']);
    }

    /**
     * @param $date
     * @return string
     */
//    public function getPolicyStartDateAttribute($date)
//    {
//        return $this->asDateTime($date)->timezone(config('app.timezone'))->format(Config::get('constants.DATE_FORMAT'));
//    }

    /**
     * @param $date
     * @return string
     */
//    public function getPolicyIssuanceDateAttribute($date)
//    {
//        return $this->asDateTime($date)->timezone(config('app.timezone'))->format(Config::get('constants.DATE_FORMAT'));
//    }

    /**
     * @param $date
     * @return string
     */
    public function getPreviousPolicyExpiryDateAttribute($date)
    {
        return $this->asDateTime($date)->timezone(config('app.timezone'))->format(Config::get('constants.DATE_FORMAT'));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function nationality()
    {
        return $this->belongsTo(Nationality::class);
    }

    /**
     * get data by personal quote type
     *
     * @param $query
     * @return mixed
     */
    public function scopeByQuoteTypeCode($query, $quoteTypeCode)
    {
        return $query->whereHas('quoteType', function ($q) use ($quoteTypeCode) {
            $q->where('code', ($quoteTypeCode));
        });
    }

    /**
     * @param $query
     * @param $quoteTypeId
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
        return $this->belongsTo(InsuranceProvider::class);
    }
}
