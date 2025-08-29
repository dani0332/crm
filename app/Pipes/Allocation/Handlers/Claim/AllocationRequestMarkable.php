<?php

namespace App\Pipes\Allocation\Handlers;

trait AllocationRequestMarkable
{
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
