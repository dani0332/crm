<?php

namespace App\Models;

use App\Enums\FilterTypes;
use App\Traits\FilterCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class PersonalQuote extends Model
{
    use HasFactory, FilterCriteria;

    protected $fillable = ['uuid', 'personal_quote_type_id', 'first_name', 'last_name', 'email', 'mobile_no', 'dob', 'nationality_id', 'uae_license_held_for_id', 'no_of_items',
        'value', 'year_of_manufacture_id' , 'insurance_provider_id'];

    public  $filterables = [
        'first_name' => FilterTypes::EXACT,
        'last_name' => FilterTypes::FREE,
        'uuid' => FilterTypes::EXACT,
        'email' => FilterTypes::EXACT,
        'mobile_no' => FilterTypes::EXACT,
        'created_at' => FilterTypes::DATE_BETWEEN
    ];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function personalQuoteType() {
        return $this->belongsTo(PersonalQuoteType::class);
    }

    /**
     * bike quote request relation
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function bikeQuote()
    {
        return $this->hasOne(BikeQuote::class);
    }

    /**
     * @param $table
     * @return string
     */
    public function getCreatedAtAttribute($table)
    {
        return $this->asDateTime($table)->timezone(config('app.timezone'))->format(Config::get('constants.datetime_format'));
    }

    /**
     * @param $table
     * @return string
     */
    public function getUpdatedAtAttribute($table)
    {
        return $this->asDateTime($table)->timezone(config('app.timezone'))->format(Config::get('constants.datetime_format'));
    }

    /**
     * get data by personal quote type
     * @param $query
     * @return mixed
     */
    public function scopeByQuoteTypeCode($query, $quoteTypeCode) {
        return $query->whereHas('personalQuoteType', function($q) use($quoteTypeCode) {
            $q->where('code', $quoteTypeCode);
        });
    }

}
