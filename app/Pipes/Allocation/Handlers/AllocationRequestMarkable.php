<?php

namespace App\Pipes\Allocation\Handlers;

trait AllocationRequestMarkable
{
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

    public function markAsSameAdvisor()
    {
        $this->set('same_advisor', true);
    }

    public function isSameAdvisor()
    {
        return $this->get('same_advisor', false);
    }
}
