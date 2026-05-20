<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\Logger\LoggerFeatureEnum;
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
    protected $signature = 'DttLife';

    private $logPrefix = 'LifeRevivalCommand - ';

    private const DELAY_IN_SECONDS = 30;

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

    private function processRevivalLeads($revivalLeads): void
    {
        $leads = $revivalLeads->values()->all();

        if ($leads === [] || count($leads) === 0) {
            LoggerService::info("{$this->logPrefix} No Life Revival Leads Jobs Found");

            return;
        }

        $logPrefix = $this->logPrefix;
        LoggerService::info("{$logPrefix} Life Revival Leads Jobs Count: ".count($leads));

        $jobs = [];
        $delayCounter = 0;

        foreach ($leads as $lead) {
            LoggerService::info("{$logPrefix} Queuing Life Revival Lead Job for lead {$lead->uuid}");
            $jobs[] = (new LifeRevivalLeadsCreationJob($lead->id))->delay(now()->addSeconds(self::DELAY_IN_SECONDS + $delayCounter));
            $delayCounter += self::DELAY_IN_SECONDS;
        }

        Bus::batch($jobs)
            ->then(function () use ($logPrefix) {
                LoggerService::info("{$logPrefix} all life revival batch jobs completed successfully");
            })
            ->catch(function () use ($logPrefix) {
                LoggerService::warning("{$logPrefix} one of life revival batch jobs failed.");
            })
            ->finally(function () use ($logPrefix) {
                LoggerService::info("{$logPrefix} life revival batch finished");
            })
            ->allowFailures()
            ->name('Life DTT Batch Jobs')
            ->dispatch();

        LoggerService::info("{$logPrefix} All Life Revival Leads Jobs dispatched");
    }
}
