<?php

namespace App\Pipes\Allocation\Handlers\Claim;

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

    public function markAsSameManager()
    {
        $this->set('same_manager', true);
    }

    public function isSameManager()
    {
        return $this->get('same_manager', false);
    }
}
