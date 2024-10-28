<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
