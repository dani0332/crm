<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class BikeQuote extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'bike_quote_request';
    protected $guarded = [];

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
        return $this->belongsTo(InsuranceProvider::class, 'currently_insured_with', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function uaeLicenseHeldFor()
    {
        return $this->belongsTo(UAELicenseHeldFor::class);
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

    /**
     * @return array
     */
    public function getAuditables()
    {
        return [
            'auditable_type' => PersonalQuote::class,
            'relations' => [
                ['auditable_type' => PersonalQuoteDetail::class, 'key' => 'personal_quote_id'],
                ['auditable_type' => BikeQuote::class, 'key' => 'personal_quote_id'],
            ],
        ];
    }
}
