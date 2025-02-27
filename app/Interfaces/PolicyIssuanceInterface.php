<?php

namespace App\Interfaces;

interface PolicyIssuanceInterface
{
    public function executeSteps($process);
    public function createPolicyIssuanceSchedule($quote, $insurer);
}
