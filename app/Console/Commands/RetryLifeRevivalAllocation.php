<?php

namespace App\Console\Commands;

use App\Enums\QuoteTypes;
use App\Services\Allocation\RetryAllocationService;
use Illuminate\Console\Command;

class RetryLifeRevivalAllocation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'RetryLifeRevivalAllocation:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will retry the life revival allocation';

    /**
     * Execute the console command.
     */
    public function handle(RetryAllocationService $retryAllocationService)
    {
        $retryAllocation = $retryAllocationService->verifyRetryAllocationMasterSwitch(QuoteTypes::LIFE);

        if (empty($retryAllocation)) {
            return Command::SUCCESS;
        }

        [$startTime, $endTime] = $retryAllocation;

        $retryAllocationService->executeLifeRevivalAllocation();
    }
}
