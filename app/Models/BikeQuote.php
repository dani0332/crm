<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class BikeQuote extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'bike_quote_request';
    protected $guarded = [];
    public $allowedColumns = ['bike_company_to_insure', 'year_of_manufacture', 'uae_license_held_for_id', 'bike_value_tier', 'make_id', 'model_id', 'currently_insured_with', 'cubic_capacity', 'emirate_of_registration_id', 'claim_history_id', 'bike_value'];

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

    public function documents()
    {
        return $this->morphMany(QuoteDocument::class, 'quote_documentable');
    }

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class, 'insurance_provider_id', 'id');
    }

    public function allowedColumns()
    {
        return $this->allowedColumns;
    }

    public function sageApiLogs()
    {
        return $this->morphMany(SageApiLog::class, 'section');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function backHomeLicenseHeldFor()
    {
        return $this->belongsTo(UAELicenseHeldFor::class, 'back_home_license_held_for_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function bikeMake()
    {
        return $this->belongsTo(CarMake::class, 'make_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function bikeModel()
    {
        return $this->belongsTo(CarModel::class, 'model_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function carTypeInsurance()
    {
        return $this->belongsTo(CarTypeInsurance::class, 'insurance_type_id');
    }

    public function batch()
    {
        return $this->hasOne(QuoteBatches::class, 'id', 'quote_batch_id')->select(['id', 'name', 'start_date', 'end_date']);
    }

    public function emirates()
    {
        return $this->belongsTo(Emirate::class, 'emirate_of_registration_id');
    }

    public function claimHistory()
    {
        return $this->belongsTo(ClaimHistory::class, 'claim_history_id');
    }

    public function advisor(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'advisor_id')->select(['id', 'email', 'name', 'mobile_no', 'landline_no', 'profile_photo_path', 'calendar_link']);
    }
}
