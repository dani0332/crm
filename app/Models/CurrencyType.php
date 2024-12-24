<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class CurrencyType extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'currency_type';

    public function scopeWithActive($query)
    {
        return $query->where('is_active', 1);
    }

    private function getCurrencyRates()
    {
        // These are rates as of Oct 15, 2024
        return [
            'AED' => 1,
            'EUR' => 4.00,
            'GBP' => 4.60,
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
