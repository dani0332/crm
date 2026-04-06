<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Jobs\Revival\LifeRevivalLeadsCreationJob;
use App\Services\Allocation\AllocationCreationService;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
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
    public function handle(AllocationCreationService $allocationCreationService)
    {
        $isDttEnabled = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::DTT_ENABLED);
        if ($isDttEnabled == false || $isDttEnabled == 0) {
            info('DTT is not enabled from cms');

            return false;
        }

        $revivalLeads = $allocationCreationService->executeLifeRevivalAllocation();

        LoggerService::info('Life Revival Leads Count: '.count($revivalLeads));
        $this->processRevivalLeads($revivalLeads);
    }

    private function processRevivalLeads($revivalLeads)
    {
        $delayCounter = 0;
        foreach ($revivalLeads as $lead) {
            if ($lead->height == null || $lead->weight == null) {
                continue;
            }
            
            $jobs[] = (new LifeRevivalLeadsCreationJob($lead))->delay(now()->addSeconds(30 + $delayCounter));
            $delayCounter += 30;
        }

        LoggerService::info('Life Revival Leads Jobs Count: '.count($jobs));
    }
}
