<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyCoverage extends Model
{
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function currency()
    {
        return $this->belongsTo(CurrencyType::class, 'currency_id');
    }
}
