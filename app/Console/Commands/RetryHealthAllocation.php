<?php

namespace App\Console\Commands;

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Services\Allocation\RetryAllocationService;
use Illuminate\Console\Command;

class RetryHealthAllocation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'RetryHealthAllocation:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This runs to retry health allocation';

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
        $retryAllocation = $retryAllocationService->verifyRetryAllocationMasterSwitch(QuoteTypes::HEALTH);

        if (empty($retryAllocation)) {
            return Command::SUCCESS;
        }

        [$startTime, $endTime] = $retryAllocation;

        $retryAllocationService->executeHealthAllocation(QuoteTypeId::Health, $endTime, 200, $startTime);
    }
}
