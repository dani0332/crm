<?php

namespace App\Models;

use App\Enums\FilterTypes;
use App\Traits\FilterCriteria;
use App\Traits\QuoteModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class HomeQuote extends Model implements AuditableContract
{
    use Auditable, FilterCriteria, HasFactory, QuoteModelTrait;

    protected $table = 'home_quote_request';
    protected $guarded = [];
    public $filterables = [
        'first_name' => FilterTypes::EXACT,
        'last_name' => FilterTypes::EXACT,
        'uuid' => FilterTypes::EXACT,
        'code' => FilterTypes::EXACT,
        'email' => FilterTypes::EXACT,
        'mobile_no' => FilterTypes::EXACT,
        'created_at' => FilterTypes::DATE_BETWEEN,
        'quote_status_id' => FilterTypes::IN,
        'advisor_id' => FilterTypes::IN,
    ];

    public function quoteStatus()
    {
        return $this->belongsTo(QuoteStatus::class);
    }

    public function homeQuoteRequestDetail()
    {
        return $this->hasOne(HomeQuoteRequestDetail::class, 'home_quote_request_id', 'id');
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

    public function payments()
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
