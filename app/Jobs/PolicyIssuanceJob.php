<?php

namespace App\Jobs;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Exceptions\PolicyIssuanceModelNotFoundException;
use App\Exceptions\PolicyIssuanceProcessNotFoundException;
use App\Models\PolicyIssuance;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Queue\SerializesModels;
use Throwable;

class PolicyIssuanceJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 180;
    public $uniqueFor = 185;
    public $tries = 1;

    private const TIMEOUT_INDICATORS = ['cURL error 28', 'has timed out', 'has been attempted too many times'];

    private int $processId;
    private ?PolicyIssuance $process = null;
    private ?string $uniqueKey = null;

    public function __construct(int $processId)
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::POLICY_ISSUANCE_JOB);
        $this->processId = $processId;
        $this->uniqueKey = "policy-issuance-job-id-{$processId}";

        $this->process = PolicyIssuance::find($processId);

        if (! $this->process) {
            LoggerService::info('Policy issuance process not found during job initialization', [
                'process_id' => $processId,
            ]);
        }
        $this->onQueue('policy-issuance-automation');
    }

    public function handle(): void
    {

        try {
            $this->loadProcess();
            $this->loadProcessModel();

            $quoteCode = $this->process->model->code;

            LoggerService::info('Starting policy issuance automation', [
                'process_id' => $this->process->id,
                'quote_code' => $quoteCode,
                'current_status' => $this->process->status,
            ]);

            if (! $this->isProcessable()) {
                LoggerService::info('Process skipped - status not processable', [
                    'process_id' => $this->process->id,
                    'quote_code' => $quoteCode,
                    'status' => $this->process->status,
                ]);

                return;
            }

            $this->updateProcessingStatus();
            $this->executeAutomation();

            $updatedProcess = $this->process?->fresh();

            if (! $updatedProcess) {
                return;
            }

            $this->process = $updatedProcess;

            $finalStatus = $updatedProcess->status;

            if ($finalStatus === PolicyIssuanceEnum::COMPLETED_STATUS) {
                LoggerService::info('Policy issuance automation completed', [
                    'process_id' => $this->process->id,
                    'quote_code' => $quoteCode,
                    'final_status' => $finalStatus,
                ]);
            } else {
                LoggerService::info('Policy issuance automation ended with status', [
                    'process_id' => $this->process->id,
                    'quote_code' => $quoteCode,
                    'final_status' => $finalStatus,
                ]);
            }

        } catch (Throwable $e) {
            if ($this->isProcessAlreadyCompleted()) {
                LoggerService::warning('Post-completion exception ignored to preserve result', [
                    'process_id' => $this->process?->id ?? $this->processId,
                    'quote_code' => $this->process?->model?->code ?? 'unknown',
                    'status' => $this->process?->status ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);

                return;
            }

            $this->handleException($e);
            $this->fail($e);
        }
    }

    public function failed(Throwable $exception): void
    {
        // Safely load process without throwing exceptions
        // The failed() callback should never throw exceptions as it prevents proper failure handling
        if (! $this->process) {
            $this->process = PolicyIssuance::find($this->processId);
        }

        LoggerService::error('Policy issuance job failed callback triggered', [
            'process_id' => $this->processId,
            'quote_code' => $this->process?->model?->code ?? 'unknown',
            'exception' => $exception->getMessage(),
            'exception_class' => get_class($exception),
        ]);

        if ($this->process) {
            $status = $this->isTimeoutError($exception->getMessage())
                ? PolicyIssuanceEnum::TIMEOUT_STATUS
                : PolicyIssuanceEnum::FAILED_STATUS;

            $this->process->update([
                'status' => $status,
                'message' => json_encode(['error' => $exception->getMessage()]),
            ]);
        } else {
            LoggerService::error('Process not found in failed callback - cannot update status', [
                'process_id' => $this->processId,
            ]);
        }
    }

    public function uniqueId(): string
    {
        if ($this->uniqueKey === null) {
            $this->uniqueKey = "policy-issuance-automation-id-{$this->processId}";
        }

        return $this->uniqueKey;
    }

    // Note: commenting this out solves the issue for "policy job attempts to many attempts"
    // public function middleware(): array
    // {
    //     return [new WithoutOverlapping($this->uniqueId())];
    // }

    private function loadProcess(): void
    {
        if (! $this->process) {
            $this->process = PolicyIssuance::find($this->processId);

            if (! $this->process) {
                LoggerService::error('Process not found - cannot proceed with automation', [
                    'process_id' => $this->processId,
                ]);
                throw new PolicyIssuanceProcessNotFoundException($this->processId);
            }
        }
    }

    private function loadProcessModel(): void
    {
        if (! $this->process->relationLoaded('model') || ! $this->process->model) {
            $this->process->load('model');
        }

        if (! $this->process->model) {
            LoggerService::error('Quote model not found for process', [
                'process_id' => $this->process->id,
            ]);

            $this->process->update([
                'status' => PolicyIssuanceEnum::FAILED_STATUS,
                'message' => json_encode(['error' => 'Quote model not found']),
            ]);

            throw new PolicyIssuanceModelNotFoundException($this->process->id);
        }

        // Refresh to get latest data
        $this->process->refresh()->load('model');
    }

    private function isProcessable(): bool
    {
        return in_array($this->process->status, [
            PolicyIssuanceEnum::PENDING_STATUS,
            PolicyIssuanceEnum::BOOKING_PENDING_STATUS,
            PolicyIssuanceEnum::TIMEOUT_STATUS,
        ]);
    }

    private function updateProcessingStatus(): void
    {
        $status = $this->process->status === PolicyIssuanceEnum::PENDING_STATUS
            ? PolicyIssuanceEnum::PROCESSING_STATUS
            : PolicyIssuanceEnum::BOOKING_PROCESSING_STATUS;

        $this->process->update(['status' => $status]);

        LoggerService::info('Process status updated to processing', [
            'process_id' => $this->process->id,
            'quote_code' => $this->process->model->code,
            'new_status' => $status,
        ]);
    }

    private function executeAutomation()
    {
        $quoteType = $this->process?->quote_type;
        $insuranceProvider = $this->process?->insuranceProvider;
        $quoteCode = $this->process?->model?->code;

        if (! $insuranceProvider) {
            LoggerService::error('Insurance provider not found for process', [
                'process_id' => $this->process?->id,
                'quote_code' => $this->process?->model?->code,
            ]);

            $this->process->update([
                'status' => PolicyIssuanceEnum::FAILED_STATUS,
                'message' => json_encode(['error' => 'Insurance provider not found']),
            ]);

            $this->fail(new \RuntimeException('Insurance provider not found'));

            return;
        }

        LoggerService::info('Insurance provider found for process', [
            'process_id' => $this->process?->id,
            'quote_code' => $this->process?->model?->code,
            'insurance_provider_code' => $insuranceProvider?->code,
        ]);

        $automation = (new PolicyIssuanceService)->init($quoteType, $insuranceProvider->code);

        if (! $automation) {
            LoggerService::error('Automation not available for insurance provider', [
                'process_id' => $this->process->id,
                'quote_code' => $quoteCode,
                'provider' => $insuranceProvider->text,
                'quote_type' => $quoteType,
                'automation' => json_encode($automation),
            ]);

            $this->process->update([
                'status' => PolicyIssuanceEnum::FAILED_STATUS,
                'message' => json_encode(['error' => "Automation not found for {$insuranceProvider->text}"]),
            ]);

            $this->fail(new \RuntimeException("Automation not found for {$insuranceProvider->text}"));

            return;
        }

        LoggerService::info('Executing automation steps', [
            'process_id' => $this->process->id,
            'quote_code' => $quoteCode,
            'provider' => $insuranceProvider->text,
        ]);

        $response = $automation->executeSteps($this->process);

        if (isset($response['timeout']) && $response['timeout']) {
            $this->process->update([
                'status' => PolicyIssuanceEnum::TIMEOUT_STATUS,
            ]);
        } elseif (! $response['status']) {
            $rawError = $response['error'] ?? 'Unknown error';
            if (! is_string($rawError)) {
                $rawError = json_encode($rawError) ?: 'Unserializable error';
            }
            $errorMessage = $rawError;

            LoggerService::info('Automation execution failed', [
                'process_id' => $this->process->id,
                'quote_code' => $quoteCode,
                'error' => $errorMessage,
                'provider' => $insuranceProvider->text,
            ]);

            $this->process->update([
                'status' => PolicyIssuanceEnum::FAILED_STATUS,
                'message' => json_encode(['error' => $errorMessage]),
            ]);

            $this->fail(new \RuntimeException($errorMessage));
        } else {
            if (isset($response['documents_pending']) && $response['documents_pending']) {
                LoggerService::info('Automation: Documents pending (async job dispatched)', [
                    'process_id' => $this->process->id,
                    'quote_code' => $quoteCode,
                    'provider' => $insuranceProvider->text,
                ]);
            } elseif (isset($response['booking_pending']) && $response['booking_pending']) {
                LoggerService::info('Automation: Booking pending', [
                    'process_id' => $this->process->id,
                    'quote_code' => $quoteCode,
                    'provider' => $insuranceProvider->text,
                ]);
                $this->process->update([
                    'status' => PolicyIssuanceEnum::BOOKING_PENDING_STATUS,
                    'message' => json_encode(['message' => 'Booking pending']),
                ]);
            } else {
                LoggerService::info('Automation executed successfully', [
                    'process_id' => $this->process->id,
                    'quote_code' => $quoteCode,
                    'provider' => $insuranceProvider->text,
                ]);
                $this->process->update(['status' => PolicyIssuanceEnum::COMPLETED_STATUS, 'message' => null]);
            }
        }
    }

    private function handleException(Throwable $e): void
    {
        if (! $this->process) {
            return;
        }

        $quoteCode = $this->process?->model?->code ?? 'unknown';

        if ($e instanceof MaxAttemptsExceededException) {
            $status = PolicyIssuanceEnum::TIMEOUT_STATUS;
            $this->process->update([
                'status' => $status,
                'message' => json_encode(['error' => 'Policy issuance job exceeded max attempts']),
            ]);
            LoggerService::info("Process marked as {$status} due to exception", [
                'process_id' => $this->process->id,
                'quote_code' => $quoteCode,
                'status' => $status,
            ]);

            return;
        }

        $status = $this->isTimeoutError($e->getMessage())
            ? PolicyIssuanceEnum::TIMEOUT_STATUS
            : PolicyIssuanceEnum::FAILED_STATUS;

        $this->process->update([
            'status' => $status,
            'message' => json_encode(['error' => $e->getMessage()]),
        ]);

        LoggerService::error('Exception occurred during policy issuance automation', [
            'process_id' => $this->process?->id ?? $this->processId,
            'quote_code' => $quoteCode,
        ], exception: $e instanceof \Exception ? $e : null, context: [
            'error_class' => get_class($e),
            'error_message' => $e->getMessage(),
            'error_trace' => $e->getTraceAsString(),
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

    private function isProcessAlreadyCompleted(): bool
    {
        return $this->process?->status === PolicyIssuanceEnum::COMPLETED_STATUS;
    }
}
