<?php

declare(strict_types=1);

namespace App\Jobs\CQF;

use App\Enums\ApplicationStorageEnums;
use App\Services\CQF\NonMotor\NonMotorCQFRegistry;
use App\Services\CQF\NonMotor\NonMotorCQFRenewalExecutionService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class ProcessNonMotorCQFOrchestratorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function handle(NonMotorCQFRenewalExecutionService $executionService): void
    {
        $renewalDaysThreshold = (int) getAppStorageValueByKey(ApplicationStorageEnums::NON_MOTOR_CQF_RENEWALS_DAYS_THRESHOLD);
        $startDate = Carbon::now()->addDays($renewalDaysThreshold);

        LoggerService::info(self::class.' - Non-motor CQF renewal orchestrator started', [
            'startDate' => $startDate->format('Y-m-d'),
            'renewalDaysThreshold' => $renewalDaysThreshold,
        ]);

        foreach (NonMotorCQFRegistry::supportedLOBs() as $quoteType) {
            if (! $executionService->hasEligibleQuotesForLOB($quoteType, $startDate)) {
                LoggerService::info(self::class.' - No eligible quotes for LOB', ['quoteType' => $quoteType->value]);

                continue;
            }

            $lead = $executionService->createLeadForLOB($quoteType);
            ProcessNonMotorCQFLOBJob::dispatch(
                $lead->id,
                $quoteType,
                $startDate->format('Y-m-d'),
                $renewalDaysThreshold
            );

            LoggerService::info(self::class.' - Dispatched LOB job', [
                'quoteType' => $quoteType->value,
                'renewalsUploadLeadsId' => $lead->id,
            ]);
        }

        LoggerService::info(self::class.' - Non-motor CQF renewal orchestrator finished');
    }
}
