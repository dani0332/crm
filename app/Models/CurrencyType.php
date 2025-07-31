<?php

namespace App\Models;

use App\Traits\Modelable;
use App\Traits\Optionable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class CurrencyType extends Model implements AuditableContract
{
    use Auditable, HasFactory, Modelable, Optionable;

    protected $table = 'currency_type';

    public function scopeWithActive($query)
    {
        return $query->where('is_active', 1);
    }

    private function getCurrencyRates(): array
    {
        // These are rates as of Feb 3, 2025
        return [
            'AED' => 1,
            'EUR' => 3.76,
            'GBP' => 4.52,
            'USD' => 3.67,
        ];
    }

    public function convertToAED(float $amount): float
    {
        $rates = $this->getCurrencyRates();

        $currencyCode = $this->code;

        if (! isset($rates[$currencyCode])) {
            throw new InvalidArgumentException("Currency code {$currencyCode} not found.");
        }

        return $amount * $rates[$currencyCode];
    }

    public function convertToUSD(float $amount): float
    {
        $amountInAED = $this->convertToAED($amount);

        $rates = $this->getCurrencyRates();

        $usdRate = $rates['USD'];

        return $amountInAED / $usdRate;
    }
}
