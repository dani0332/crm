<?php

namespace App\Models;

use App\Traits\Modelable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class CurrencyType extends Model implements AuditableContract
{
    use Auditable, HasFactory, Modelable;

    protected $table = 'currency_type';

    public function scopeWithActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function getAED(float $amount): float
    {
        // These are rates as of Feb 3, 2025
        $rates = [
            'AED' => 1,
            'EUR' => 3.76,
            'GBP' => 4.52,
            'USD' => 3.67,
        ];

        $currencyCode = $this->code;

        $rate = $rates[$currencyCode] ?? 1;

        return $amount * $rate;
    }

    public function getUSD(float $amount): float
    {
        $rates = [
            'AED' => 0.27,
            'EUR' => 1.05,
            'GBP' => 1.26,
            'USD' => 1,
        ];

        $currencyCode = $this->code;

        $rate = $rates[$currencyCode] ?? 1;

        return $amount * $rate;
    }
}
