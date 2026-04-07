<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Jobs\Revival\LifeRevivalLeadsCreationJob;
use App\Services\Allocation\AllocationCreationService;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class LifeRevivalCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'LifeRevival';

    private $logPrefix = 'LifeRevivalCommand - ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will run the life leads revival process';

    /**
     * Execute the console command.
     */
    public function handle(AllocationCreationService $allocationCreationService)
    {
        $isDttEnabled = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::DTT_ENABLED);
        if ($isDttEnabled == false || $isDttEnabled == 0) {
            LoggerService::info("{$this->logPrefix} DTT is not enabled from cms");

            return false;
        }

        $revivalLeads = $allocationCreationService->executeLifeRevivalAllocation();

        $this->processRevivalLeads($revivalLeads);
    }

    private function processRevivalLeads($revivalLeads)
    {
        // Filter out the leads with null height or weight
        $jobs = $revivalLeads->filter(fn ($lead) => $lead->height != null && $lead->weight != null)
            ->values()
            ->map(function ($lead, $index) {
                return (new LifeRevivalLeadsCreationJob($lead))->delay(now()->addSeconds(30 + ($index * 30)));
            })
            ->all();

        // Execute jobs in batch
        if ($jobs != null && count($jobs)) {
            LoggerService::info("{$this->logPrefix} Life Revival Leads Jobs Count: ".count($jobs));
            $this->executeJobsInBatch($jobs[0]);
        } else {
            LoggerService::info("{$this->logPrefix} No Life Revival Leads Jobs Found");
        }
    }

    private function executeJobsInBatch($jobs)
    {
        $logPrefix = $this->logPrefix;

        Bus::batch($jobs)
            ->then(function () use ($logPrefix) {
                LoggerService::info("{$logPrefix} All Life Revival Leads Jobs Completed");
            })
            ->catch(function () use ($logPrefix) {
                LoggerService::error("{$logPrefix} Some of the Life Revival Leads Jobs Failed");
            })
            ->finally(function () use ($logPrefix) {
                LoggerService::info("{$logPrefix} Life Revival Leads Jobs Finished");
            })
            ->allowFailures()
            ->name('Life Revival Leads Jobs')
            ->dispatch();
    }
}
