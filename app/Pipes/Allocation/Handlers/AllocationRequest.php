<?php

namespace App\Pipes\Allocation\Handlers;

use App\Enums\AssignmentTypeEnum;
use App\Enums\QuoteTypes;
use App\Models\NationalityAllocationConfiguration;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AllocationRequest
{
    use AllocationRequestable;

    protected Collection $collection;

    public function __construct(
        protected QuoteTypes $quoteType,
        protected $quoteUUID,
        protected $teamId,
        protected $overrideAdvisorId,
        protected bool $isReassignmentJob = false,
        protected $assignmentType = AssignmentTypeEnum::SYSTEM_ASSIGNED
    ) {
        $this->collection = new Collection;
    }

    public function markAsAllocated()
    {
        $this->set('allocated', true);
    }

    public function isAllocated()
    {
        return $this->get('allocated', false);
    }

    public function markAsFailed()
    {
        $this->set('failed', true);
    }

    public function isFailed()
    {
        return $this->get('failed', false);
    }

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

    public function setNationalityConfig(NationalityAllocationConfiguration $config)
    {
        $this->set('nationality_config', $config);
    }

    public function hasNationalityConfig()
    {
        return ! empty($this->get('nationality_config', null));
    }

    public function setAdvisorIDs(array $advisorIds)
    {
        $this->set('advisor_ids', $advisorIds);
    }

    public function getAdvisorIDs()
    {
        return $this->get('advisor_ids', []);
    }

    public function markAsSIC()
    {
        $this->set('sic', true);
    }

    public function isSIC()
    {
        return $this->get('sic', false);
    }
}
