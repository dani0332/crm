<?php

namespace App\Services;

use App\Models\HealthPlanCoPayment;

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
}
