<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Config;

class BusinessQuote extends Model implements AuditableContract
{
    use HasFactory, Auditable;
    protected $table = 'business_quote_request';
    protected $guarded = [];

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
}
