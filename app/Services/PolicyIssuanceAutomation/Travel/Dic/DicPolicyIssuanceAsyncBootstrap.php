<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Travel\Dic;

use App\Enums\InsuranceProviderEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Jobs\DicPolicyIssuanceStepJob;
use App\Jobs\PolicyIssuanceJob;
use App\Models\PolicyIssuance;
use App\Models\TravelQuote;
use App\Services\Logger\LoggerService;
use Illuminate\Support\Facades\Bus;

/**
 * Kicks off the Bus-based DIC Travel pipeline from {@see PolicyIssuanceJob} so the job stays thin.
 */
final class DicPolicyIssuanceAsyncBootstrap
{
    public function __construct(
        private DicInsuranceService $dicInsuranceService,
    ) {}

    public static function isTravelDic(?string $quoteType, ?string $insuranceProviderCode): bool
    {
        return $quoteType === QuoteTypes::TRAVEL->value
            && $insuranceProviderCode === InsuranceProviderEnum::DIC->value;
    }

    public function dispatchInitialStepFromOrchestratorJob(PolicyIssuance $process): void
    {
        $quoteCode = $process->model?->code;

        if (! $this->dicInsuranceService->isPolicyIssuanceAutomationEnabled()) {
            $process->update([
                'status' => PolicyIssuanceEnum::FAILED_STATUS,
                'message' => json_encode(['error' => 'DIC Travel automation is disabled']),
            ]);
        } elseif (! ($quote = $process->model) instanceof TravelQuote) {
            $process->update([
                'status' => PolicyIssuanceEnum::FAILED_STATUS,
                'message' => json_encode(['error' => 'Invalid quote model for DIC Travel automation']),
            ]);
        } elseif (! (($validation = $this->dicInsuranceService->validateBeforeDicAsyncRun($quote))['status'])) {
            $errorMessage = $validation['error'] ?? $validation['message'] ?? 'DIC validation failed';
            $process->update([
                'status' => PolicyIssuanceEnum::FAILED_STATUS,
                'message' => json_encode(['error' => $errorMessage]),
            ]);
        } else {
            $nextStep = $this->dicInsuranceService->resolveAsyncStepToRun($process);
            if ($nextStep === null) {
                $process->update(['status' => PolicyIssuanceEnum::COMPLETED_STATUS]);
            } else {
                Bus::dispatch(new DicPolicyIssuanceStepJob($process->id, $nextStep));

                LoggerService::info('DIC Travel: first async step dispatched via Bus', [
                    'process_id' => $process->id,
                    'quote_code' => $quoteCode,
                    'step' => $nextStep,
                ]);
            }
        }
    }
}
