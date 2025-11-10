<?php

namespace App\Jobs;

use App\Enums\PolicyIssuanceEnum;
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
use Illuminate\Support\Facades\Log;
use Throwable;

class PolicyIssuanceJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 180; // 3 minutes
    public $uniqueFor = 185;
    public $tries = 1; // Changed from 3 to 1 - only try once, no retries

    private const TIMEOUT_MESSAGE = 'cURL error 28';
    private const LARAVEL_TIMEOUT_MESSAGE = 'has timed out';

    private $className = 'policyIssuanceJob';
    public mixed $process;
    public $uniqueKey = null;
    private $processId;

    /**
     * Create a new job instance.
     */
    public function __construct($processId)
    {
        LoggerService::info('job:'.$this->className.' fn:'.__FUNCTION__.' Process ID : '.$processId.' inside constructor');
        $this->processId = $processId;
        $this->uniqueKey = 'policy-issuance-automation-id-'.$processId;
        $this->process = PolicyIssuance::find($processId);
        if (! $this->process) {
            LoggerService::info('job:'.$this->className.' fn:'.__FUNCTION__.' Process ID : '.$processId.' not found');
            return;
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Reload process if it wasn't found in constructor or if it's null
            if (! $this->process) {
                $this->process = PolicyIssuance::find($this->processId);
                if (! $this->process) {
                    LoggerService::info('job:'.$this->className.' fn:'.__FUNCTION__.' Process ID : '.$this->processId.' not found, skipping job execution');
                    return;
                }
            }

            // Ensure model relationship is loaded
            if (! $this->process->relationLoaded('model') || ! $this->process->model) {
                $this->process->load('model');
                if (! $this->process->model) {
                    LoggerService::info('job:'.$this->className.' fn:'.__FUNCTION__.' Process ID : '.$this->process->id.' - Model not found, skipping job execution');
                    $this->process->update(['status' => PolicyIssuanceEnum::FAILED_STATUS, 'message' => json_encode(['error' => 'Model not found'])]);
                    return;
                }
            }

            LoggerService::info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Process ID : '.$this->process->id.' Started');

            $this->process = $this->process->refresh();
            $this->process->load('model');

            if ($this->isProcessable($this->process)) {
                $processingStatus = $this->process->status === PolicyIssuanceEnum::PENDING_STATUS ? PolicyIssuanceEnum::PROCESSING_STATUS : PolicyIssuanceEnum::BOOKING_PROCESSING_STATUS;
                $this->process->update(['status' => $processingStatus]);
                LoggerService::info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Process ID : '.$this->process->id.' updated to : '.$this->process->status);

                $quoteType = $this->process?->quote_type;
                $insuranceProvider = $this->process?->insuranceProvider;

                if (! $insuranceProvider) {
                    LoggerService::info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Insurance Provider not found');
                    $this->process->update(['status' => PolicyIssuanceEnum::FAILED_STATUS, 'message' => json_encode(['error' => 'Insurance Provider not found'])]);
                    return;
                }

                $insuranceProviderAutomation = (new PolicyIssuanceService)->init($quoteType, $insuranceProvider->code);
                if ($insuranceProviderAutomation) {
                    $response = $insuranceProviderAutomation->executeSteps($this->process);
                    LoggerService::info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' Response : ', $response);
                    if (! $response['status']) {
                        $this->process->update(['status' => PolicyIssuanceEnum::FAILED_STATUS, 'message' => json_encode(['error' => $response['error']])]);
                        LoggerService::info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Process ID : '.$this->process->id.' updated to : '.$this->process->status.' Error : ', extra: ['error' => $response['error']]);
                    } else {
                        $this->process->update(['status' => PolicyIssuanceEnum::COMPLETED_STATUS]);
                        LoggerService::info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Process ID : '.$this->process->id.' updated to : '.$this->process->status);
                    }
                } else {
                    LoggerService::info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - '.$insuranceProvider->text.' Automation not found');
                    $this->process->update(['status' => PolicyIssuanceEnum::FAILED_STATUS, 'message' => json_encode(['error' => 'Automation not found for '.$insuranceProvider->text])]);
                }
            } else {
                LoggerService::info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Process ID : '.$this->process->id.' Status : '.$this->process->status.' is skipped.');
            }

            LoggerService::info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Process ID : '.$this->process->id.' completed');
        } catch (\Throwable $e) {
            // Log the exception with full details
            Log::error('job:'.$this->className.' fn:'.__FUNCTION__.' exception caught', [
                'process_id' => $this->process->id ?? 'unknown',
                'exception_class' => get_class($e),
                'exception_message' => $e->getMessage(),
                'exception_file' => $e->getFile(),
                'exception_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString(),
            ]);

            // Mark as failed and handle accordingly
            if ($this->process) {
                $messageLower = strtolower($e->getMessage());
                
                // Check if it's a timeout error
                if (str_contains($messageLower, strtolower(self::TIMEOUT_MESSAGE)) || 
                    str_contains($messageLower, strtolower(self::LARAVEL_TIMEOUT_MESSAGE))) {
                    $this->process->update(['status' => PolicyIssuanceEnum::TIMEOUT_STATUS, 'message' => json_encode(['error' => $e->getMessage()])]);
                    LoggerService::info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.($this->process->model->code ?? 'unknown').' - Process ID : '.$this->process->id.' marked as timeout');
                } else {
                    $this->process->update(['status' => PolicyIssuanceEnum::FAILED_STATUS, 'message' => json_encode(['error' => $e->getMessage()])]);
                    LoggerService::info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.($this->process->model->code ?? 'unknown').' - Process ID : '.$this->process->id.' marked as failed');
                }
            }

            // DO NOT re-throw the exception - this prevents retries
            // The job will complete "successfully" from Laravel's perspective
            // but the process status is marked as failed/timeout in the database
        }
    }

    public function failed(Throwable $exception)
    {
        // This should rarely be called now since we catch everything in handle()
        // But keep it as a safety net
        
        if (! $this->process) {
            $this->process = PolicyIssuance::find($this->processId);
        }

        $message = $exception->getMessage();

        Log::error('job:'.$this->className.' fn:'.__FUNCTION__.' reached failed() method', [
            'process_id' => $this->process->id ?? $this->processId,
            'exception_class' => get_class($exception),
            'exception_message' => $message,
            'current_status' => $this->process->status ?? 'unknown',
        ]);

        if ($this->process) {
            $messageLower = strtolower($message);
            if (str_contains($messageLower, strtolower(self::TIMEOUT_MESSAGE)) || 
                str_contains($messageLower, strtolower(self::LARAVEL_TIMEOUT_MESSAGE))) {
                $this->process->update(['status' => PolicyIssuanceEnum::TIMEOUT_STATUS, 'message' => json_encode(['error' => $message])]);
            } else {
                $this->process->update(['status' => PolicyIssuanceEnum::FAILED_STATUS, 'message' => json_encode(['error' => $message])]);
            }
        }
    }

    public function uniqueId(): string
    {
        return $this->uniqueKey ?? 'policy-issuance-automation-id-'.$this->processId;
    }

    public function middleware()
    {
        return [new WithoutOverlapping($this->uniqueKey)];
    }

    private function isProcessable($process)
    {
        return in_array($process->status, [
            PolicyIssuanceEnum::PENDING_STATUS, 
            PolicyIssuanceEnum::BOOKING_PENDING_STATUS, 
            PolicyIssuanceEnum::TIMEOUT_STATUS
        ]);
    }
}