<?php

namespace App\Interfaces;

interface PolicyIssuanceInterface
{
    public function handle($process);
    public function createPolicyIssuanceSchedule($quote, $insurer);
}
