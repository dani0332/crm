<?php

declare(strict_types=1);

namespace App\Jobs\CQF;

use App\Enums\ApplicationStorageEnums;
use App\Services\CQF\NonMotor\NonMotorCQFRegistry;
use App\Services\CQF\NonMotor\NonMotorCQFRenewalExecutionService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Throwable;

class ProcessNonMotorCQFOrchestratorJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Shared prefix for all batch names in the Non-motor CQF renewal pipeline.
     * The orchestrator batch is suffixed with "- Orchestrator"; each per-LOB batch with "- {LOB}".
     * See: ProcessNonMotorCQFLOBJob::lobBatchName()
     */
    public const BATCH_NAME_PREFIX = 'Non Motor CQF Renewal Orchestrator';

    public int $tries = 1;
    public int $timeout = 180;
    public int $uniqueFor = 300;

    public function handle(NonMotorCQFRenewalExecutionService $executionService): void
    {
        $renewalDaysThreshold = (int) (getAppStorageValueByKey(ApplicationStorageEnums::NON_MOTOR_CQF_RENEWALS_DAYS_THRESHOLD) ?: 120);
        $startDate = Carbon::now()->addDays($renewalDaysThreshold);

        LoggerService::info(self::class.' - Non-motor CQF renewal orchestrator started', [
            'startDate' => $startDate->format(config('constants.DATE_FORMAT_ONLY')),
            'renewalDaysThreshold' => $renewalDaysThreshold,
        ]);

        $lobJobs = [];
        foreach (NonMotorCQFRegistry::supportedLOBs() as $quoteType) {
            $totalRecords = $executionService->getEligibleQuoteCountForLOB($quoteType, $startDate);

            if ($totalRecords === 0) {
                LoggerService::info(self::class.' - No eligible quotes for LOB', ['quoteType' => $quoteType->value]);

                continue;
            }

            $renewalUploadLeads = $executionService->createRenewalUploadLeadsForLOB($quoteType, $totalRecords);
            $lobJobs[] = new ProcessNonMotorCQFLOBJob(
                $renewalUploadLeads->id,
                $quoteType,
                $startDate->format(config('constants.DATE_FORMAT_ONLY')),
                $renewalDaysThreshold
            );

            LoggerService::info(self::class.' - Queued LOB job for batch', [
                'quoteType' => $quoteType->value,
                'renewalsUploadLeadsId' => $renewalUploadLeads->id,
            ]);
        }

        if (! empty($lobJobs)) {
            $batchName = self::BATCH_NAME_PREFIX.' - '.now()->format(config('constants.DATE_FORMAT_ONLY'));
            Bus::batch($lobJobs)
                ->name($batchName)
                ->allowFailures()
                ->onQueue('default')
                ->dispatch();

            LoggerService::info(self::class.' - Dispatched batch', [
                'batchName' => $batchName,
                'jobCount' => count($lobJobs),
            ]);
        }

        LoggerService::info(self::class.' - Non-motor CQF renewal orchestrator finished');
    }

    public function failed(Throwable $exception): void
    {
        LoggerService::error(self::class.' - Orchestrator job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
