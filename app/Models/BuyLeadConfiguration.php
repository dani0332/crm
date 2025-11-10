<?php

namespace App\Models;

use App\Enums\BuyLeadSegment;
use App\Enums\LeadSourceEnum;
use App\Observers\BuyLeadConfigurationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(BuyLeadConfigurationObserver::class)]
class BuyLeadConfiguration extends Model
{
    protected $fillable = [
        'quote_type_id',
        'department_id',
        'value',
        'volume',
        'segment',
        'source',
    ];

    public function casts()
    {
        return [
            'value' => 'float',
            'volume' => 'float',
            'segment' => BuyLeadSegment::class,
            'source' => LeadSourceEnum::class,
        ];
    }

    public function scopeIsSIC($query)
    {
        $query->where('segment', BuyLeadSegment::SIC);
    }

    public function scopeIsNonSIC($query)
    {
        $query->where('segment', BuyLeadSegment::NON_SIC);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function quoteType()
    {
        return $this->belongsTo(QuoteType::class);
    }

    public function nationalities()
    {
        return $this->belongsToMany(Nationality::class, 'buy_lead_config_nationalities', 'buy_lead_configuration_id', 'nationality_id');
    }
}
