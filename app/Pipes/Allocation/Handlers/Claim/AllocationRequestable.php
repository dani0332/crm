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

    public function setManager(User $manager)
    {
        $this->set('manager', $manager);
    }

    public function getManager()
    {
        return $this->get('manager');
    }

    public function setManagerIDs(array $managerIds)
    {
        $this->set('manager_ids', $managerIds);
    }

    public function getManagerIDs()
    {
        return $this->get('manager_ids', []);
    }
}
