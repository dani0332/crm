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

class BusinessQuote extends Model implements AuditableContract
{
    use HasFactory, FilterCriteria, Auditable, QuoteModelTrait;

    protected $table = 'business_quote_request';
    protected $guarded = [];
    public $filterables = [
        'first_name' => FilterTypes::EXACT,
        'last_name' => FilterTypes::FREE,
        'uuid' => FilterTypes::EXACT,
        'code' => FilterTypes::EXACT,
        'email' => FilterTypes::EXACT,
        'mobile_no' => FilterTypes::EXACT,
        'created_at' => FilterTypes::DATE_BETWEEN,
        'quote_status_id' => FilterTypes::IN,
        'advisor_id' => FilterTypes::IN,
    ];

    public function getCreatedAtAttribute($table)
    {
        $date_time_format = Config::get('constants.datetime_format');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }

    public function getUpdatedAtAttribute($table)
    {
        $date_time_format = Config::get('constants.datetime_format');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }

    public function quoteStatus()
    {
        return $this->hasOne(QuoteStatus::class, 'id', 'quote_status_id');
    }

    public function businessQuoteRequestDetail()
    {
        return $this->hasOne(BusinessQuoteRequestDetail::class, 'business_quote_request_id', 'id');
    }

    public function typeOfInsurance()
    {
        return $this->hasOne(BusinessInsuranceType::class, 'id', 'business_type_of_insurance_id');
    }

    public function advisor()
    {
        return $this->hasOne(User::class, 'id', 'advisor_id');
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }
}
