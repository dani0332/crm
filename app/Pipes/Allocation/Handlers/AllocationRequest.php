<?php

namespace App\Pipes\Allocation\Handlers;

use App\Enums\AssignmentTypeEnum;
use App\Enums\QuoteTypes;
use Illuminate\Support\Collection;

class AllocationRequest
{
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

    public function getQuoteType()
    {
        return $this->quoteType;
    }

    public function getQuoteUUID()
    {
        return $this->quoteUUID;
    }

    public function getTeamId()
    {
        return $this->teamId;
    }

    public function getOverrideAdvisorId()
    {
        return $this->overrideAdvisorId;
    }

    public function isReassignmentJob()
    {
        return $this->isReassignmentJob;
    }

    public function getAssignmentType()
    {
        return $this->assignmentType;
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
}
