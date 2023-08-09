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

class HomeQuote extends Model implements AuditableContract
{
    use HasFactory, FilterCriteria, Auditable, QuoteModelTrait;

    protected $table = 'home_quote_request';
    protected $guarded = [];
    public $filterables = [
        'first_name' => FilterTypes::FREE,
        'last_name' => FilterTypes::FREE,
        'previous_quote_policy_number' => FilterTypes::EXACT,
        'code' => FilterTypes::EXACT,
        'email' => FilterTypes::EXACT,
        'source' => FilterTypes::EXACT,
        'renewal_expiry_date' => FilterTypes::DATE_BETWEEN,
        'uuid' => FilterTypes::EXACT,
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
        return $this->belongsTo(QuoteStatus::class);
    }

    public function homeQuoteRequestDetail()
    {
        return $this->hasOne(HomeQuoteRequestDetail::class, 'home_quote_request_id', 'id');
    }

    public function insuranceProvider()
    {
        return $this->hasOne(InsuranceProvider::class, 'text', 'currently_insured_with')->select(['id', 'text']);
    }
    public function nationality()
    {
        return $this->hasOne(Nationality::class, 'id', 'nationality_id')->select(['id', 'code', 'text']);
    }

    public function accommodationType()
    {
        return $this->belongsTo(HomeAccomodationType::class, 'ilivein_accommodation_type_id');
    }

    public function possessionType()
    {
        return $this->belongsTo(HomePossessionType::class, 'iam_possesion_type_id');
    }

    public function advisor()
    {
        return $this->belongsTo(User::class, 'advisor_id')->select(['id', 'email', 'name']);
    }
}
