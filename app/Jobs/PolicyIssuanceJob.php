<?php

namespace App\Jobs;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Models\PolicyIssuance;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

class PolicyIssuanceJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 180;
    public $uniqueFor = 185;
    public $tries = 1;

    private const TIMEOUT_INDICATORS = ['cURL error 28', 'has timed out'];

    private int $processId;
    private ?PolicyIssuance $process = null;
    private string $uniqueKey;

    public function __construct(int $processId)
    {
        $this->processId = $processId;
        $this->uniqueKey = "policy-issuance-automation-id-{$processId}";
        
        $this->process = PolicyIssuance::find($processId);
        
        if (!$this->process) {
            LoggerService::info("Policy issuance process not found during job initialization", [
                'process_id' => $processId
            ]);
        }
    }

    public function handle(): void
    {
        try {
            $this->loadProcess();
            $this->loadProcessModel();
            
            
            $quoteCode = $this->process->model->code;
            
            LoggerService::info("Starting policy issuance automation", [
                'process_id' => $this->process->id,
                'quote_code' => $quoteCode,
                'current_status' => $this->process->status
            ]);

            if (!$this->isProcessable()) {
                LoggerService::info("Process skipped - status not processable", [
                    'process_id' => $this->process->id,
                    'quote_code' => $quoteCode,
                    'status' => $this->process->status
                ]);
                return;
            }

            $this->updateProcessingStatus();
            $this->executeAutomation();

            LoggerService::info("Policy issuance automation completed", [
                'process_id' => $this->process->id,
                'quote_code' => $quoteCode,
                'final_status' => $this->process->fresh()->status
            ]);
            LoggerService::endLogging();

        } catch (Throwable $e) {
            $this->handleException($e);
            LoggerService::endLogging();
        }
    }

    public function failed(Throwable $exception): void
    {
        // Safely load process without throwing exceptions
        // The failed() callback should never throw exceptions as it prevents proper failure handling
        if (!$this->process) {
            $this->process = PolicyIssuance::find($this->processId);
        }
        
        LoggerService::error("Policy issuance job failed callback triggered", [
            'process_id' => $this->processId,
            'quote_code' => $this->process?->model?->code ?? 'unknown',
            'exception' => $exception->getMessage(),
            'exception_class' => get_class($exception)
        ]);

        if ($this->process) {
            $status = $this->isTimeoutError($exception->getMessage()) 
                ? PolicyIssuanceEnum::TIMEOUT_STATUS 
                : PolicyIssuanceEnum::FAILED_STATUS;

            $this->process->update([
                'status' => $status,
                'message' => json_encode(['error' => $exception->getMessage()])
            ]);
        } else {
            LoggerService::error("Process not found in failed callback - cannot update status", [
                'process_id' => $this->processId
            ]);
        }
    }

    public function uniqueId(): string
    {
        return $this->uniqueKey;
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->uniqueKey)];
    }

    private function loadProcess(): void
    {
        if (!$this->process) {
            $this->process = PolicyIssuance::find($this->processId);

            if (!$this->process) {
                LoggerService::error("Process not found - cannot proceed with automation", [
                    'process_id' => $this->processId
                ]);
                throw new \RuntimeException("Process {$this->processId} not found");
            }
        }

        LoggerService::startQuoteLogging(
            QuoteTypes::getName($this->process->quote_type)->refId($this->process->model?->code),
            LoggerFeatureEnum::POLICY_ISSUANCE_JOB
        );
    }

    private function loadProcessModel(): void
    {
        if (!$this->process->relationLoaded('model') || !$this->process->model) {
            $this->process->load('model');
        }

        if (!$this->process->model) {
            LoggerService::error("Quote model not found for process", [
                'process_id' => $this->process->id
            ]);

            $this->process->update([
                'status' => PolicyIssuanceEnum::FAILED_STATUS,
                'message' => json_encode(['error' => 'Quote model not found'])
            ]);

            throw new \RuntimeException("Model not found for process {$this->process->id}");
        }

        // Refresh to get latest data
        $this->process->refresh()->load('model');
    }

    private function isProcessable(): bool
    {
        return in_array($this->process->status, [
            PolicyIssuanceEnum::PENDING_STATUS,
            PolicyIssuanceEnum::BOOKING_PENDING_STATUS,
            PolicyIssuanceEnum::TIMEOUT_STATUS
        ]);
    }

    private function updateProcessingStatus(): void
    {
        $status = $this->process->status === PolicyIssuanceEnum::PENDING_STATUS
            ? PolicyIssuanceEnum::PROCESSING_STATUS
            : PolicyIssuanceEnum::BOOKING_PROCESSING_STATUS;

        $this->process->update(['status' => $status]);

        LoggerService::info("Process status updated to processing", [
            'process_id' => $this->process->id,
            'quote_code' => $this->process->model->code,
            'new_status' => $status
        ]);
    }

    private function executeAutomation(): void
    {
        $quoteType = $this->process?->quote_type;
        $insuranceProvider = $this->process?->insuranceProvider;
        $quoteCode = $this->process?->model?->code;

        if (!$insuranceProvider) {
            LoggerService::error("Insurance provider not found for process", [
                'process_id' => $this->process?->id,
                'quote_code' => $this->process?->model?->code
            ]);

            $this->process->update([
                'status' => PolicyIssuanceEnum::FAILED_STATUS,
                'message' => json_encode(['error' => 'Insurance provider not found'])
            ]);
            return;
        }

        $automation = (new PolicyIssuanceService)->init($quoteType, $insuranceProvider->code);

        if (!$automation) {
            LoggerService::error("Automation not available for insurance provider", [
                'process_id' => $this->process->id,
                'quote_code' => $quoteCode,
                'provider' => $insuranceProvider->text,
                'quote_type' => $quoteType
            ]);

            $this->process->update([
                'status' => PolicyIssuanceEnum::FAILED_STATUS,
                'message' => json_encode(['error' => "Automation not found for {$insuranceProvider->text}"])
            ]);
            return;
        }

        LoggerService::info("Executing automation steps", [
            'process_id' => $this->process->id,
            'quote_code' => $quoteCode,
            'provider' => $insuranceProvider->text
        ]);

        $response = $automation->executeSteps($this->process);

        if (!$response['status']) {
            LoggerService::error("Automation execution failed", [
                'process_id' => $this->process->id,
                'quote_code' => $quoteCode,
                'error' => $response['error'] ?? 'Unknown error',
                'provider' => $insuranceProvider->text
            ]);

            $this->process->update([
                'status' => PolicyIssuanceEnum::FAILED_STATUS,
                'message' => json_encode(['error' => $response['error']])
            ]);
        } else {
            LoggerService::info("Automation executed successfully", [
                'process_id' => $this->process->id,
                'quote_code' => $quoteCode,
                'provider' => $insuranceProvider->text
            ]);

            $this->process->update(['status' => PolicyIssuanceEnum::COMPLETED_STATUS]);
        }
    }

    private function handleException(Throwable $e): void
    {
        $quoteCode = $this->process?->model?->code ?? 'unknown';

        LoggerService::error("Exception occurred during policy issuance automation", [
            'process_id' => $this->process->id ?? $this->processId,
            'quote_code' => $quoteCode
        ], exception: $e);

        if (!$this->process) {
            return;
        }

        $status = $this->isTimeoutError($e->getMessage())
            ? PolicyIssuanceEnum::TIMEOUT_STATUS
            : PolicyIssuanceEnum::FAILED_STATUS;

        $this->process->update([
            'status' => $status,
            'message' => json_encode(['error' => $e->getMessage()])
        ]);

        LoggerService::info("Process marked as {$status} due to exception", [
            'process_id' => $this->process->id,
            'quote_code' => $quoteCode,
            'status' => $status
        ]);
    }

    private function isTimeoutError(string $message): bool
    {
        $messageLower = strtolower($message);
        
        foreach (self::TIMEOUT_INDICATORS as $indicator) {
            if (str_contains($messageLower, strtolower($indicator))) {
                return true;
            }
        }
        
        return false;
    }
}
