<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\Logger\LoggerFeatureEnum;
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

    private const REVIVAL_DISPATCH_CHUNK_SIZE = 500;
    private const DELAY_BETWEEN_JOBS = 10;

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
        LoggerService::startFeatureLogging(LoggerFeatureEnum::LIFE_REVIVAL);
        LoggerService::info(self::class.' - handle - starting life revival command');

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
        $leads = $revivalLeads->values()->all();

        if ($leads === [] || count($leads) === 0) {
            LoggerService::info("{$this->logPrefix} No Life Revival Leads Jobs Found");

            return;
        }

        LoggerService::info("{$this->logPrefix} Life Revival Leads Jobs Count: ".count($leads));

        $chunks = array_chunk($leads, self::REVIVAL_DISPATCH_CHUNK_SIZE);
        $delayOffsetSeconds = 0;

        foreach ($chunks as $index => $chunk) {
            LoggerService::info("{$this->logPrefix} Dispatching chunk ".($index + 1).' of '.count($chunks).' ('.count($chunk).' leads)');
            $this->executeJobs($chunk, $delayOffsetSeconds);
            $delayOffsetSeconds += count($chunk) * self::DELAY_BETWEEN_JOBS;
        }

        LoggerService::info("{$this->logPrefix} All Life Revival Leads Jobs dispatched");
    }

    private function executeJobs(array $leads, int $initialDelaySeconds = 0): void
    {
        $logPrefix = $this->logPrefix;
        $delayInSeconds = $initialDelaySeconds;

        foreach ($leads as $lead) {
            LoggerService::info("{$logPrefix} Dispatching Life Revival Lead Job for lead {$lead->uuid}");
            LifeRevivalLeadsCreationJob::dispatch($lead->id)->delay(now()->addSeconds($delayInSeconds));
            $delayInSeconds += self::DELAY_BETWEEN_JOBS;
        }
    }
}
