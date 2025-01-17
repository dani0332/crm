<?php

namespace App\Services\ProcessTracker;

use App\Enums\ProcessTracker\ProcessTrackerTypeEnum;
use App\Enums\QuoteTypes;
use App\Models\ProcessTracker\Tracker;
use App\Models\ProcessTracker\TrackerProcess;
use Illuminate\Support\Collection;

trait ProcessTrackable
{
    protected Collection $steps;
    protected Tracker $processTracker;
    protected TrackerProcess $process;

    public function initQuoteProcess(ProcessTrackerTypeEnum $processType, QuoteTypes $quoteType, string $uuid)
    {
        $this->processTracker = Tracker::initQuoteModel($quoteType, $uuid);

        return $this->addProcess($processType);
    }
}
