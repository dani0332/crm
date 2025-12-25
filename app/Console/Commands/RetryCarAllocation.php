<?php

namespace App\Console\Commands;

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Services\Allocation\RetryAllocationService;
use Illuminate\Console\Command;

class RetryCarAllocation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'RetryCarAllocation:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This runs to retry car allocation';

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
        $retryAllocation = $retryAllocationService->verifyRetryAllocationMasterSwitch(QuoteTypes::CAR);

        if (empty($retryAllocation)) {
            return Command::SUCCESS;
        }

        [$startTime, $endTime] = $retryAllocation;

        $retryAllocationService->executeCarAllocation(QuoteTypeId::Car, $endTime, 200);
    }
}
