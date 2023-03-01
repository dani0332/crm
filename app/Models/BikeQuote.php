<?php

namespace App\Models;

use Config;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class BikeQuote extends Model implements AuditableContract
{
    use HasFactory, Auditable;

    protected $table = 'bike_quote_request';
    protected $guarded = [];

    public function getCreatedAtAttribute($table)
    {
        $date_time_format = Config::get('constants.datetime_format');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function manufactureYear()
    {
        return $this->belongsTo(YearOfManufacture::class, 'year_of_manufacture', 'text');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currentlyInsuredWith()
    {
        return $this->belongsTo(InsuranceProvider::class, 'currently_insured_with','id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function uaeLicenseHeldFor()
    {
        return $this->belongsTo(UAELicenseHeldFor::class);
    }

    public function getUpdatedAtAttribute($table)
    {
        $date_time_format = Config::get('constants.datetime_format');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function quoteStatus()
    {
        return $this->hasOne(QuoteStatus::class, 'id', 'quote_status_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function bikeQuoteRequestDetail()
    {
        return $this->hasOne(BikeQuoteRequestDetail::class, 'bike_quote_request_id', 'id');
    }
}
