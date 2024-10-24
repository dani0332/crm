<?php

namespace App\Jobs;

use App\Enums\PolicyIssuanceEnum;
use App\Factories\PolicyIssuanceFactory;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Log;
use Throwable;

class PolicyIssuanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private mixed $process;

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
        $this->process->update(['status' => PolicyIssuanceEnum::PROCESSING_STATUS]);
        info('job:'.basename(__CLASS__).' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Process status updated to : '.$this->process->status);

        $quoteType = $this->process->quote_type;
        $insuranceProvider = $this->process->insuranceProvider;
        if (! $insuranceProvider) {
            info('job:'.basename(__CLASS__).' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Insurance Provider not found');

            return;
        }

        $insuranceProviderAutomation = PolicyIssuanceFactory::make($quoteType, $insuranceProvider->code);
        if (! $insuranceProviderAutomation) {
            info('job:'.basename(__CLASS__).' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - '.$insuranceProvider->text.' Automation not found');

            return;
        }

        $response = $insuranceProviderAutomation->handle($this->process);
        info('job:'.basename(__CLASS__).' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' Response : '.json_encode($response));
        if (! $response['status']) {
            $this->process->update(['status' => PolicyIssuanceEnum::FAILED_STATUS, 'message' => json_encode(['error' => $response['error']])]);
            info('job:'.basename(__CLASS__).' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Process status updated to : '.$this->process->status.' Error : '.json_encode($response['error']));
        } else {
            $this->process->update(['status' => PolicyIssuanceEnum::COMPLETED_STATUS]);
            info('job:'.basename(__CLASS__).' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' - Process status updated to : '.$this->process->status);
        }
    }

    public function failed(Throwable $exception)
    {
        $this->process->update(['status' => PolicyIssuanceEnum::FAILED_STATUS, 'message' => json_encode(['error' => $exception->getMessage()])]);

        Log::error('job:'.basename(__CLASS__).' fn:'.__FUNCTION__.' Quote :  '.$this->process->model->code.' Error : '.$exception->getMessage());
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->process->model->code.'-'.Carbon::now()->format('YmdHi')))->dontRelease()];
    }
}
