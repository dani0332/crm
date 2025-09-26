<?php

namespace App\Services\CQF;

class CarCQFRenewalService
{
    public function processCarCQFRenewalLeads()
    {
        app(CarCQFRenewalOrchestratorService::class)->processCarCQFRenewalLeads();
    }

}
