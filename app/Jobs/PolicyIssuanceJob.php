<?php

namespace App\Jobs;

use App\Enums\PolicyIssuanceEnum;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Log;
use Throwable;

class PolicyIssuanceJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const TIMEOUT_MESSAGE = 'cURL error 28';

    // 28 is the cURL error code for timeout
    private $className = 'policyIssuanceJob';
    private mixed $process;
    public $uniqueFor = 60 * 15; // 15 minutes

    /**
     * Create a new job instance.
     */
    public function __construct($process)
    {
        $this->process = $process;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        info('cmd:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Process ID : '.$this->process->id.' Started');

        if ($this->isProcessable($this->process)) {

            $this->process->update(['status' => PolicyIssuanceEnum::PROCESSING_STATUS]);
            info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Process ID : '.$this->process->id.' updated to : '.$this->process->status);

            $quoteType = $this->process?->quote_type;
            $insuranceProvider = $this->process?->insuranceProvider;

            if (! $insuranceProvider) {
                info('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Insurance Provider not found');

                return;
            }

            $insuranceProviderAutomation = (new PolicyIssuanceService)->make($quoteType, $insuranceProvider->code);
            if ($insuranceProviderAutomation) {
                $response = $insuranceProviderAutomation->handle($this->process);
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

        info('cmd:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Process ID : '.$this->process->id.' completed');
    }

    public function failed(Throwable $exception)
    {
        $message = $exception->getMessage();
        if (str_contains($message, self::TIMEOUT_MESSAGE)) {
            $this->process->update(['status' => PolicyIssuanceEnum::TIMEOUT_STATUS, 'message' => json_encode(['error' => $exception->getMessage()])]);
        } else {
            $this->process->update(['status' => PolicyIssuanceEnum::FAILED_STATUS, 'message' => json_encode(['error' => $exception->getMessage()])]);
        }
        Log::error('job:'.$this->className.' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Process ID : '.$this->process->id.' updated to : '.$this->process->status.' Error : '.$exception->getMessage());
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->process->model->code.'-'.Carbon::now()->format('YmdHi')))->dontRelease()];
    }

    public function uniqueId(): string
    {
        return $this->process->model->code;
    }

    private function isProcessable($process)
    {
        return in_array($process->status, [PolicyIssuanceEnum::PENDING_STATUS, PolicyIssuanceEnum::TIMEOUT_STATUS]);
    }

}
