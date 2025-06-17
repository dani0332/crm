<?php

namespace App\Models;

use App\Enums\LookupsEnum;
use App\Enums\QuoteTypeId;
use Illuminate\Database\Eloquent\Model;

class SavingsQuote extends Model
{
    protected $table = 'savings_quote_request';
    protected $fillable = [
        'personal_quote_id',
        'marital_status_id',
        'tenure_id',
        'purpose_id',
        'currency_id',
        'investment_amount',
        'investment_criteria_id',
        'additional_notes',
    ];

    public function getAuditables()
    {
        return [
            'auditable_type' => PersonalQuote::class,
            'relations' => [
                ['auditable_type' => PersonalQuoteDetail::class, 'key' => 'personal_quote_id'],
                ['auditable_type' => self::class, 'key' => 'personal_quote_id'],
            ],
        ];
    }

    public function purpose()
    {
        return $this->belongsTo(Lookup::class, 'purpose_id', 'id')->where('key', LookupsEnum::SAVINGS_PURPOSE->value)->where('quote_type_id', QuoteTypeId::Savings);
    }

    public function investmentFrequency()
    {
        return $this->belongsTo(Lookup::class, 'investment_criteria_id', 'id')->where('key', LookupsEnum::INVESTMENT_TYPE->value)->where('quote_type_id', QuoteTypeId::Savings);
    }

    public function tenure()
    {
        return $this->belongsTo(Lookup::class, 'tenure_id', 'id')->where('key', LookupsEnum::SAVINGS_TENURE->value)->where('quote_type_id', QuoteTypeId::Savings);
    }

    public function currency()
    {
        return $this->belongsTo(CurrencyType::class);
    }

    public function maritalStatus()
    {
        return $this->belongsTo(MartialStatus::class);
    }

    public function personalQuote()
    {
        return $this->belongsTo(PersonalQuote::class);
    }
}
