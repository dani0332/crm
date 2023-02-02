<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalQuote extends Model
{
    use HasFactory;
    protected $fillable = ['uuid', 'personal_quote_type_id', 'first_name', 'last_name', 'email', 'mobile_no', 'dob', 'nationality_id', 'uae_license_held_for_id', 'no_of_items',
        'value', 'year_of_manufacture_id' , 'insurance_provider_id'];

    public  $filterable = [
        'first_name',
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
