<?php

namespace App\Console\Commands;

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Services\Allocation\RetryAllocationService;
use Illuminate\Console\Command;

class RetryAllocation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'RetryAllocation:cron {quoteType}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This runs to retry allocation';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(RetryAllocationService $retryAllocationService)
    {
        $quoteType = QuoteTypes::from($this->argument('quoteType'));
        $retryAllocation = $retryAllocationService->verifyRetryAllocationMasterSwitch($quoteType);

        if (empty($retryAllocation)) {
            return;
        }

        [$startTime, $endTime] = $retryAllocation;

        $retryAllocationService->executeAllocation($quoteType, $endTime, 200, $startTime);
    }
}
