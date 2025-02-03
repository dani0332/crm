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

    private function getCurrencyRates()
    {
        // These are rates as of Feb 3, 2025
        return [
            'AED' => 1,
            'EUR' => 3.76,
            'GBP' => 4.52,
            'USD' => 3.67,
        ];
    }

    public function getAED(float $amount): float
    {
        $currencyCode = $this->code;

        $rates = $this->getCurrencyRates();

        return $amount * ($rates[$currencyCode] ?? 1);
    }
}
