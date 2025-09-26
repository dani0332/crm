<?php

namespace App\Services\CQF;

use App\Services\CQF\CarCQFRenewalOrchestratorService;



class CarCQFRenewalService
{
   
    public function processCarCQFRenewalLeads()
    { 
        app(CarCQFRenewalOrchestratorService::class)->processCarCQFRenewalLeads();
    }

}
