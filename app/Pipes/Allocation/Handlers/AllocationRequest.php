<?php

namespace App\Pipes\Allocation\Handlers;

use App\Enums\AssignmentTypeEnum;
use App\Enums\QuoteTypes;
use Illuminate\Support\Collection;

class AllocationRequest
{
    use AllocationRequestable, AllocationRequestMarkable;

    protected Collection $collection;

    public function __construct(
        protected QuoteTypes $quoteType,
        protected $quoteUUID,
        protected $teamId = null,
        protected $overrideAdvisorId = false,
        protected bool $isReassignmentJob = false,
        protected $assignmentType = AssignmentTypeEnum::SYSTEM_ASSIGNED,
        protected $evaluateTierOnly = false,
        protected $reAssigFromAdvisorId = null,
        protected $source = null,
        protected bool $assignToHappinessUser = false,
    ) {
        $this->collection = new Collection;

        $this->reAssigFromAdvisorId = ! empty($this->reAssigFromAdvisorId) && $this->reAssigFromAdvisorId != 0 ? $this->reAssigFromAdvisorId : null;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function getQuoteType()
    {
        return $this->quoteType;
    }

    public function getQuoteUUID()
    {
        return $this->quoteUUID;
    }

    public function setTeamId($teamId)
    {
        $this->teamId = $teamId;
    }

    public function getTeamId()
    {
        return $this->teamId;
    }

    public function overrideAdvisorId(bool $override = true)
    {
        $this->overrideAdvisorId = $override;
    }

    public function isOverrideAdvisorRequest()
    {
        return $this->overrideAdvisorId;
    }

    public function isReassignmentJob()
    {
        return $this->isReassignmentJob;
    }

    public function setAsReassignmentJob()
    {
        $this->isReassignmentJob = true;
    }

    public function getAssignmentType()
    {
        return $this->assignmentType;
    }

    public function isEvaluateTierOnlyRequest()
    {
        return $this->evaluateTierOnly;
    }

    public function getReAssigFromAdvisorId()
    {
        return $this->reAssigFromAdvisorId;
    }

    public function getRefID()
    {
        return $this->getQuoteType()->refId($this->quoteUUID);
    }

    public function model()
    {
        return $this->getQuoteType()->model();
    }

    public function set($key, $value)
    {
        $this->collection->put($key, $value);
    }

    public function get($key, $default = null)
    {
        return $this->collection->get($key, $default);
    }

    public function endBuyLeadProcessing()
    {
        if ($this->isBuyLead() && $this->getBuyLeadRequest()) {
            $this->getBuyLeadRequest()->completeProcessing();
        }
    }

}
