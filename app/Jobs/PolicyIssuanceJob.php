<?php

namespace App\Jobs;

use App\Enums\EnvEnum;
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

    public $timeout = 120;
    public $tries = 1; // Back to 1 since we fixed the constructor issue

    private const TIMEOUT_MESSAGE = 'cURL error 28';
    private const LARAVEL_TIMEOUT_MESSAGE = 'has timed out';

    // 28 is the cURL error code for timeout
    private $className = 'policyIssuanceJob';
    public mixed $process = null;
    public $uniqueFor = 120; // Reduced to 2 minutes (should be enough for processing)
    private int $processId;

    /**
     * Create a new job instance.
     */
    public function __construct($processId)
    {
        $this->timeout = config('constants.APP_ENV') == EnvEnum::PRODUCTION ? 90 : 120;
        
        // Store the ID for later use
        $this->processId = is_object($processId) ? $processId->id : $processId;
        
        // Log constructor start
        Log::info('PolicyIssuanceJob Constructor - ProcessID: ' . $this->processId);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Load the process fresh from database
        $this->process = PolicyIssuance::find($this->processId);
        
        if (!$this->process) {
            Log::error('PolicyIssuanceJob - Process not found with ID: ' . $this->processId);
            return;
        }
        
        LoggerService::info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Process ID : '.$this->process->id.' Started');
        $this->process = $this->process->refresh();
        $quote = $this->process->model;


        if ($this->isProcessable($this->process)) {
            $processingStatus = $this->process->status === PolicyIssuanceEnum::PENDING_STATUS ? PolicyIssuanceEnum::PROCESSING_STATUS : PolicyIssuanceEnum::BOOKING_PROCESSING_STATUS;
            $this->process->update(['status' => $processingStatus]);
            LoggerService::info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Process ID : '.$this->process->id.' updated to : '.$this->process->status);

            $quoteType = $this->process?->quote_type;
            $insuranceProvider = $this->process?->insuranceProvider;

            if (! $insuranceProvider) {
                LoggerService::info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Insurance Provider not found');

                return;
            }

            $insuranceProviderAutomation = (new PolicyIssuanceService)->init($quoteType, $insuranceProvider->code);
            if ($insuranceProviderAutomation) {
                $response = $insuranceProviderAutomation->executeSteps($this->process);
                LoggerService::info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' Response : '.json_encode($response));
                if (! $response['status']) {
                    $this->process->update(['status' => PolicyIssuanceEnum::FAILED_STATUS, 'message' => json_encode(['error' => $response['error']])]);
                    LoggerService::info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Process ID : '.$this->process->id.' updated to : '.$this->process->status.' Error : '.json_encode($response['error']));
                } else {
                    $this->process->update(['status' => PolicyIssuanceEnum::COMPLETED_STATUS]);
                    LoggerService::info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Process ID : '.$this->process->id.' updated to : '.$this->process->status);
                }

            } else {
                LoggerService::info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - '.$insuranceProvider->text.' Automation not found');
            }

        } else {
            LoggerService::info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Process ID : '.$this->process->id.' Status : '.$this->process->status.' is skipped.');
        }

        LoggerService::info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Process ID : '.$this->process->id.' completed');
    }

    public function failed(Throwable $exception)
    {
        // Load process if not already loaded
        if (!$this->process) {
            $this->process = PolicyIssuance::find($this->processId);
        }

        if (!$this->process) {
            Log::error('PolicyIssuanceJob failed - Process not found with ID: ' . $this->processId . ' Error: ' . $exception->getMessage());
            return;
        }

        $message = $exception->getMessage();
        if (str_contains($message, self::TIMEOUT_MESSAGE) || str_contains($message, self::LARAVEL_TIMEOUT_MESSAGE)) {
            $this->process->update(['status' => PolicyIssuanceEnum::TIMEOUT_STATUS, 'message' => json_encode(['error' => $exception->getMessage()])]);
        } else {
            $this->process->update(['status' => PolicyIssuanceEnum::FAILED_STATUS, 'message' => json_encode(['error' => $exception->getMessage()])]);
        }
        Log::error('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Process ID : '.$this->process->id.' updated to : '.$this->process->status.' Error : '.$exception->getMessage());
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        $timestamp = now()->timestamp;
        return 'policy-issuance-' . $this->processId . '-' . $timestamp;
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        // Use expireAfter instead of dontRelease to ensure locks are automatically cleared
        return [(new WithoutOverlapping($this->uniqueId()))->releaseAfter(120)];
    }

    private function isProcessable($process)
    {
        return in_array($process->status, [PolicyIssuanceEnum::PENDING_STATUS, PolicyIssuanceEnum::BOOKING_PENDING_STATUS, PolicyIssuanceEnum::TIMEOUT_STATUS]);
    }

}
