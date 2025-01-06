<?php

namespace App\Models;

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
    ];

    public function casts()
    {
        return [
            'value' => 'float',
            'volume' => 'float',
        ];
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function quoteType()
    {
        return $this->belongsTo(QuoteType::class);
    }
}
