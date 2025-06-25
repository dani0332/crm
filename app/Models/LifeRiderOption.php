<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LifeRiderOption extends Model
{
    protected $table = 'life_rider_option';

    function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    function currencyCoverages()
    {
        return $this->hasMany(CurrencyCoverage::class, 'life_rider_option_id');
    }
}
