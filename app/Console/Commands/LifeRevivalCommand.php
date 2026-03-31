<?php

namespace App\Console\Commands;

use App\Enums\QuoteTypes;
use App\Services\Allocation\RetryAllocationService;
use Illuminate\Console\Command;

class LifeRevivalCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'LifeRevival';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will run the life revival process';

    /**
     * Execute the console command.
     */
    public function handle(RetryAllocationService $retryAllocationService)
    {
        $retryAllocation = $retryAllocationService->verifyRetryAllocationMasterSwitch(QuoteTypes::LIFE);

        if (empty($retryAllocation)) {
            return Command::SUCCESS;
        }

        $retryAllocationService->executeLifeRevivalAllocation(); // change to revivalservice (generic for all lob)
    }
}
