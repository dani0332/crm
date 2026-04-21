<?php

namespace App\Console\Commands;

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Services\Allocation\RetryAllocationService;
use App\Services\BuyLeads\BuyLeadService;
use App\Services\BuyLeads\CatARevivalAllocationPriorityService;
use Illuminate\Console\Command;

class RetryCarRevivalAllocation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'RetryCarRevivalAllocation:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This runs to retry car revival allocation';

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

        if (BuyLeadService::getNationalitiesIds(QuoteTypes::CAR_CAT_A) !== []) {
            $startTime = now()->subDays(CatARevivalAllocationPriorityService::lookbackDays())->startOfDay()->toDateTimeString();
        }

        $retryAllocationService->executeCarRevivalAllocation(QuoteTypeId::Car, $endTime, 200, $startTime);
    }
}
