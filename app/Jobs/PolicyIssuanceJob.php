<?php

namespace App\Jobs;

use App\Enums\PolicyIssuanceEnum;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class PolicyIssuanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 3;

    private const TIMEOUT_MESSAGE = 'cURL error 28';
    private const LARAVEL_TIMEOUT_MESSAGE = 'has timed out';

    // 28 is the cURL error code for timeout
    private $className = 'policyIssuanceJob';
    public mixed $process;
    public $uniqueKey = null;

    /**
     * Create a new job instance.
     */
    public function __construct($process)
    {
        $this->process = $process;
        $this->uniqueKey = 'policy-issuance-automation-id-'.$this->process->id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->process = $this->process->refresh();

            info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Process ID : '.$this->process->id.' Started');

            if ($this->isProcessable($this->process)) {
                $processingStatus = $this->process->status === PolicyIssuanceEnum::PENDING_STATUS ? PolicyIssuanceEnum::PROCESSING_STATUS : PolicyIssuanceEnum::BOOKING_PROCESSING_STATUS;
                $this->process->update(['status' => $processingStatus]);
                info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Process ID : '.$this->process->id.' updated to : '.$this->process->status);

                $quoteType = $this->process?->quote_type;
                $insuranceProvider = $this->process?->insuranceProvider;

                if (! $insuranceProvider) {
                    info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Insurance Provider not found');

                    return;
                }

                $insuranceProviderAutomation = (new PolicyIssuanceService)->init($quoteType, $insuranceProvider->code);
                if ($insuranceProviderAutomation) {
                    $response = $insuranceProviderAutomation->executeSteps($this->process);
                    info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' Response : '.json_encode($response));
                    if (! $response['status']) {
                        $this->process->update(['status' => PolicyIssuanceEnum::FAILED_STATUS, 'message' => json_encode(['error' => $response['error']])]);
                        info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Process ID : '.$this->process->id.' updated to : '.$this->process->status.' Error : '.json_encode($response['error']));
                    } else {
                        $this->process->update(['status' => PolicyIssuanceEnum::COMPLETED_STATUS]);
                        info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Process ID : '.$this->process->id.' updated to : '.$this->process->status);
                    }

                } else {
                    info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - '.$insuranceProvider->text.' Automation not found');
                }
            } else {
                info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Process ID : '.$this->process->id.' Status : '.$this->process->status.' is skipped.');
            }

            info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Process ID : '.$this->process->id.' completed');
        } catch (\Throwable $e) {
            // Catch any exception that occurs during job execution
            // This will capture the ORIGINAL exception before it becomes "attempted too many times"
            Log::error('job:'.$this->className.' fn:'.__FUNCTION__.' exception caught', [
                'process_id' => $this->process->id ?? 'unknown',
                'exception_class' => get_class($e),
                'exception_message' => $e->getMessage(),
                'exception_file' => $e->getFile(),
                'exception_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception)
    {
        $message = $exception->getMessage();

        // Log full exception details including stack trace for debugging
        Log::error('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Process ID : '.$this->process->id.' EXCEPTION DETAILS', [
            'exception_class' => get_class($exception),
            'exception_message' => $message,
            'exception_code' => $exception->getCode(),
            'exception_file' => $exception->getFile(),
            'exception_line' => $exception->getLine(),
            'stack_trace' => $exception->getTraceAsString(),
            'current_status' => $this->process->status ?? 'unknown',
        ]);

        if (str_contains($message, self::TIMEOUT_MESSAGE) || str_contains($message, self::LARAVEL_TIMEOUT_MESSAGE)) {
            $this->process->update(['status' => PolicyIssuanceEnum::TIMEOUT_STATUS, 'message' => json_encode(['error' => $exception->getMessage()])]);
        } else {
            $this->process->update(['status' => PolicyIssuanceEnum::FAILED_STATUS, 'message' => json_encode(['error' => $exception->getMessage()])]);
        }
        Log::error('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Process ID : '.$this->process->id.' updated to : '.$this->process->status.' Error : '.$exception->getMessage());
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->uniqueKey))->dontRelease()];
    }

    private function isProcessable($process)
    {
        return in_array($process->status, [PolicyIssuanceEnum::PENDING_STATUS, PolicyIssuanceEnum::BOOKING_PENDING_STATUS, PolicyIssuanceEnum::TIMEOUT_STATUS]);
    }

}
