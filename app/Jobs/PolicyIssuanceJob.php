<?php

namespace App\Jobs;

use App\Enums\EnvEnum;
use App\Enums\PolicyIssuanceEnum;
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

    public $timeout = 90;
    public $tries = 1;

    private const TIMEOUT_MESSAGE = 'cURL error 28';
    private const LARAVEL_TIMEOUT_MESSAGE = 'has timed out';

    // 28 is the cURL error code for timeout
    private $className = 'policyIssuanceJob';
    public mixed $process;
    public $uniqueFor = 60 * 15; // 15 minutes
    public $uniqueKey = null; // 15 minutes

    /**
     * Create a new job instance.
     */
    public function __construct($process)
    {
        $this->timeout = config('constants.APP_ENV') == EnvEnum::PRODUCTION ? 90 : 120;
        $this->process = $process;
        $this->uniqueKey = 'policy-issuance-automation-id-'.$this->process->id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->process = $this->process->refresh();
        $quote = $this->process->model;

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
    }

    public function failed(Throwable $exception)
    {
        $message = $exception->getMessage();
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

    public function uniqueId(): string
    {
        return $this->uniqueKey;
    }

    private function isProcessable($process)
    {
        return in_array($process->status, [PolicyIssuanceEnum::PENDING_STATUS, PolicyIssuanceEnum::BOOKING_PENDING_STATUS, PolicyIssuanceEnum::TIMEOUT_STATUS]);
    }

}
