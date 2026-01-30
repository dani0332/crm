<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor;

class NonMotorCQFRenewalService
{
    public function processNonMotorCQFRenewalLeads(): void
    {
        app(NonMotorCQFRenewalExecutionService::class)->processNonMotorCQFRenewalLeads();
    }
}
