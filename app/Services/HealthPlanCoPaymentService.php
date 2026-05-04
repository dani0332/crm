<?php

namespace App\Services;

use App\Models\HealthPlanCoPayment;
use Illuminate\Support\Collection;

class HealthPlanCoPaymentService extends BaseService
{
    public function getAllCoPayments(): ?array
    {
        return HealthPlanCoPayment::distinct()->pluck('code')->toArray();
    }

    public function getByAttribute(string $attribute, $value): ?HealthPlanCoPayment
    {
        return HealthPlanCoPayment::where($attribute, $value)->first();
    }

    public function getByCodes(array $codes): Collection
    {
        return HealthPlanCoPayment::whereIn('code', $codes)
            ->get()
            ->keyBy('code');
    }
}
