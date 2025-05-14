<?php

namespace App\Pipes\Allocation\Handlers;

trait AllocationRequestable
{
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

    public function isOverrideAdvisorRequest()
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
}
