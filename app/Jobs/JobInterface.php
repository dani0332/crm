<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

abstract class JobInterface implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    abstract protected function fetchLeads();

    abstract protected function findTier($tierId);

    abstract protected function findAvailableUsers($lead);

    abstract protected function findRules($lead);

    abstract protected function finalizeAdvisors($lead, $tier, $users, $rules);

    abstract protected function assignLeadAndSendEmail($lead, $userId, $tier);
}
