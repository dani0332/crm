<?php

namespace App\Observers;

use App\Enums\InsuranceProvidersEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Models\PolicyIssuance;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;

class PolicyIssuanceObserver
{
    private $className = 'PolicyIssuanceObserver';
    /**
     * Handle the PolicyIssuance "updated" event.
     */
    public function updated(PolicyIssuance $policyIssuance): void
    {
        $dirty = $policyIssuance->getDirty();
        if (
            $policyIssuance->isDirty('status') &&
            $policyIssuance->status === PolicyIssuanceEnum::BOOKING_PENDING_STATUS &&
            $policyIssuance->insuranceProvider->code === InsuranceProvidersEnum::AXA
        ) {
            LoggerService::info($this->className.' fn:'.__FUNCTION__.' Started');
            app(PolicyIssuanceService::class)->executePolicyIssuanceAutomationSteps();
            LoggerService::info($this->className.' fn:'.__FUNCTION__.' Ended');
        }
    }
}
