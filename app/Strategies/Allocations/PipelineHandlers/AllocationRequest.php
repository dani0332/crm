<?php

namespace App\Strategies\Allocations\PipelineHandlers;

use App\Enums\QuoteTypes;
use App\Services\ProcessTracker\ProcessTrackerService;
use Illuminate\Support\Collection;

class AllocationRequest
{
    protected Collection $collection;

    public function __construct(
        protected QuoteTypes $quoteType,
        protected $quoteUUID,
        protected $teamId,
        protected $overrideAdvisorId,
        protected ?ProcessTrackerService $tracker = null
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

    public function getTracker()
    {
        return $this->tracker;
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
