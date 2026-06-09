<?php

declare(strict_types=1);

namespace App\Jobs\CQF;

use App\Enums\ApplicationStorageEnums;
use App\Models\RenewalsUploadLeads;
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
     * See: ProcessNonMotorCQFLOBJob::lobBatchName() (protected)
     */
    public const BATCH_NAME_PREFIX = 'Non Motor CQF Renewal Orchestrator';

    private const DEFAULT_RENEWAL_DAYS_THRESHOLD = 120;

    // Single try is intentional — the orchestrator is idempotent and re-triggered manually if needed.
    public int $tries = 1;

    // Keep timeout under the queue connection's retry_after (90) so a slow run is never
    // released and re-run concurrently. The orchestrator only counts eligible quotes and
    // creates lead rows before dispatching the batch, so 60s is ample.
    public int $timeout = 60;
    public int $uniqueFor = 360;

    public function handle(NonMotorCQFRenewalExecutionService $executionService): void
    {
        $renewalDaysThreshold = (int) (getAppStorageValueByKey(ApplicationStorageEnums::NON_MOTOR_CQF_RENEWALS_DAYS_THRESHOLD) ?: self::DEFAULT_RENEWAL_DAYS_THRESHOLD);
        $startDate = Carbon::now()->addDays($renewalDaysThreshold);

        LoggerService::info(self::class.' - Non-motor CQF renewal orchestrator started', [
            'startDate' => $startDate->format(config('constants.DATE_FORMAT_ONLY')),
            'renewalDaysThreshold' => $renewalDaysThreshold,
        ]);

        $lobJobs = [];
        $createdLeadIds = [];
        foreach (NonMotorCQFRegistry::supportedLOBs() as $quoteType) {
            $totalRecords = $executionService->getEligibleQuoteCountForLOB($quoteType, $startDate);

            if ($totalRecords === 0) {
                LoggerService::info(self::class.' - No eligible quotes for LOB', ['quoteType' => $quoteType->value]);

                continue;
            }

            $renewalUploadLeads = $executionService->createRenewalUploadLeadsForLOB($quoteType, $totalRecords);
            $createdLeadIds[] = $renewalUploadLeads->id;
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
            try {
                Bus::batch($lobJobs)
                    ->name($batchName)
                    ->allowFailures()
                    ->onQueue('default')
                    ->dispatch();
            } catch (Throwable $e) {
                RenewalsUploadLeads::whereIn('id', $createdLeadIds)->delete();
                throw $e;
            }

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
