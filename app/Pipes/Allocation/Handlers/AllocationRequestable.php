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

    public function markAsSIC()
    {
        $this->set('sic', true);
    }

    public function isSIC()
    {
        return $this->get('sic', false);
    }

    public function markAsBuyLead()
    {
        $this->set('buy_lead', true);
    }

    public function isBuyLead()
    {
        return $this->get('buy_lead', false);
    }

    public function markAsAIG()
    {
        $this->set('aig', true);
    }

    public function isAIG()
    {
        return $this->get('aig', false);
    }
}
