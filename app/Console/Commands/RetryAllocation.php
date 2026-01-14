<?php

namespace App\Console\Commands;

use App\Enums\QuoteTypes;
use App\Services\Allocation\RetryAllocationService;
use App\Services\Logger\LoggerService;
use Illuminate\Console\Command;

class RetryAllocation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'RetryAllocation:cron {--quoteType= : The quote type to retry allocation for}';

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
        try {
            $quoteType = QuoteTypes::from($this->option('quoteType'));
        } catch (\Exception $e) {
            LoggerService::error('Invalid quote type: '.$this->option('quoteType'));

            return Command::FAILURE;
        }

        $retryAllocation = $retryAllocationService->verifyRetryAllocationMasterSwitch($quoteType);

        if (empty($retryAllocation)) {
            return Command::SUCCESS;
        }

        [$startTime, $endTime] = $retryAllocation;

        $retryAllocationService->executeAllocation($quoteType, $endTime, 200, $startTime);
    }
}
