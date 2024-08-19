<?php

namespace App\Strategies;

interface Allocation
{
    public function executeSteps($overrideAdvisorId);
}
