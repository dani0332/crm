<?php

namespace App\Models;

use App\Enums\InvestmentFrequencyEnum;
use App\Enums\SavingsPurposeEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class SavingsQuote extends Model
{
    protected $table = 'savings_quote_request';
    protected $appends = [
        'purpose',
        'frequency',
    ];
    protected $fillable = [
        'personal_quote_id',
        'marital_status_id',
        'tenure_of_savings',
        'has_nicotine',
        'purpose_of_savings',
        'currency_id',
        'amount',
        'investment_frequency',
        'additional_notes',
    ];
    protected $casts = [
        'purpose_of_savings' => SavingsPurposeEnum::class,
        'investment_frequency' => InvestmentFrequencyEnum::class,
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

    public function purpose(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->purpose_of_savings?->label(),
        );
    }

    public function frequency(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->investment_frequency?->label(),
        );
    }

    public function currency()
    {
        return $this->belongsTo(CurrencyType::class);
    }
}
