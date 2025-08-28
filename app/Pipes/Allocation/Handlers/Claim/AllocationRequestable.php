<?php

namespace App\Pipes\Allocation\Handlers\Claim;


use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait AllocationRequestable
{
    public function setLead(Model $lead)
    {
        $this->set('lead', $lead);
    }

    public function getLead()
    {
        return $this->get('lead');
    }

    public function setAdvisor(User $advisor)
    {
        $this->set('advisor', $advisor);
    }

    public function getAdvisor()
    {
        return $this->get('advisor');
    }

    public function setAdvisorIDs(array $advisorIds)
    {
        $this->set('advisor_ids', $advisorIds);
    }

    public function getAdvisorIDs()
    {
        return $this->get('advisor_ids', []);
    }
}
