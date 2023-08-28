<?php

namespace App\Models;

use App\Enums\FilterTypes;
use App\Traits\FilterCriteria;
use App\Traits\QuoteModelTrait;
use Config;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class LifeQuote extends Model implements AuditableContract
{
    use HasFactory, FilterCriteria, Auditable, QuoteModelTrait;

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
    ];

    public function getDobAttribute($table)
    {
        $date_format = Config::get('constants.DATE_FORMAT');

        return $this->asDate($table)->timezone(config('app.timezone'))->format($date_format);
    }

    public function quoteStatus()
    {
        return $this->hasOne(QuoteStatus::class, 'id', 'quote_status_id');
    }

    public function lifeQuoteRequestDetail()
    {
        return $this->hasOne(LifeQuoteRequestDetail::class, 'life_quote_request_id', 'id');
    }
    public function advisor()
    {
        return $this->belongsTo(User::class)->select(['id', 'email', 'name']);
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
    public function childern()
    {
        return $this->belongsTo(LifeChildren::class, 'children_id', 'id');
    }
    public function currency()
    {
        return $this->belongsTo(CurrencyType::class, 'sum_insured_currency_id', 'id');
    }
    public function insuranceTenure()
    {
        return $this->belongsTo(LifeInsuranceTenure::class, 'tenure_of_insurance_id', 'id');
    }
    public function numberOfYears()
    {
        return $this->belongsTo(LifeNumberOfYears::class, 'number_of_years_id', 'id');
    } public function maritalStatus()
    {
        return $this->belongsTo(MartialStatus::class, 'marital_status_id', 'id');
    }public function paymentStatus()
    {
        return $this->belongsTo(PaymentStatus::class, 'payment_status_id', 'id');
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
