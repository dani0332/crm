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
    protected $signature = 'DttLife';

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
        $isDttEnabled = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::DTT_LIFE_ENABLED);
        if ($isDttEnabled == false || $isDttEnabled == 0) {
            LoggerService::info("{$this->logPrefix} DTT Life Revival is not enabled from cms");

            return false;
        }

        $revivalLeads = $allocationCreationService->executeLifeRevivalAllocation();

        $this->processRevivalLeads($revivalLeads);
    }

    private function processRevivalLeads($revivalLeads)
    {
        // Filter out the leads with null height or weight
        $leads = $revivalLeads->filter(fn ($lead) => $lead->lifeQuote->height != null && $lead->lifeQuote->weight != null)
            ->values()
            ->all();

        if ($leads != null && count($leads)) {
            LoggerService::info("{$this->logPrefix} Life Revival Leads Jobs Count: ".count($leads));
            $this->executeJobs($leads);
        } else {
            LoggerService::info("{$this->logPrefix} No Life Revival Leads Jobs Found");
        }
    }

    private function executeJobs(array $leads)
    {
        $logPrefix = $this->logPrefix;

        foreach ($leads as $lead) {
            LoggerService::info("{$logPrefix} Dispatching Life Revival Lead Job for lead {$lead->uuid}");
            LifeRevivalLeadsCreationJob::dispatch($lead->id);
            // Give some time before dispatching the next job (like car dtt revival job)
            sleep(10);
        }
        LoggerService::info("{$logPrefix} All Life Revival Leads Jobs dispatched");
    }
}
