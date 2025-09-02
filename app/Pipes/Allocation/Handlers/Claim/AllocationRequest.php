<?php

namespace App\Pipes\Allocation\Handlers\Claim;

use App\Enums\AssignmentTypeEnum;
use App\Enums\QuoteTypes;
use App\Models\ClaimRequest;
use App\Pipes\Allocation\Handlers\AllocationRequestMarkable;
use Illuminate\Support\Collection;

class AllocationRequest
{
    use AllocationRequestable, AllocationRequestMarkable;

    protected Collection $collection;

    public function __construct(
        protected QuoteTypes $quoteType,
        protected $claimUUID,
        protected $assignmentType = AssignmentTypeEnum::SYSTEM_ASSIGNED,
        protected $isReassignmentJob = false,
    ) {
        $this->collection = new Collection;

    }

    public function getQuoteType()
    {
        return $this->quoteType;
    }

    public function getClaimUUID()
    {
        return $this->claimUUID;
    }
    public function isReassignmentJob()
    {
        return $this->isReassignmentJob;
    }

    public function getAssignmentType()
    {
        return $this->assignmentType;
    }

    public function set($key, $value)
    {
        $this->collection->put($key, $value);
    }

    public function get($key, $default = null)
    {
        return $this->collection->get($key, $default);
    }
    /**
     * Get a new instance of the ClaimRequest model.
     */
    public function model(): ClaimRequest
    {
        return new ClaimRequest;
    }
}
