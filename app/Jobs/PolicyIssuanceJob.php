<?php

namespace App\Jobs;

use App\Enums\PolicyIssuanceEnum;
use App\Factories\PolicyIssuanceFactory;
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

        $quoteType = $this->process->quote_type;
        $insuranceProvider = $this->process->insuranceProvider;
        if (! $insuranceProvider) {
            info(__CLASS__.' fn:'.__FUNCTION__.' - Insurance Provider not found');
            return;
        }

        $insuranceProviderAutomation = PolicyIssuanceFactory::make($quoteType, $insuranceProvider->code);
        if (! $insuranceProviderAutomation) {
            info(__CLASS__.' fn:'.__FUNCTION__.' - '.$insuranceProvider->text.' Automation not found');
            return;
        }

        $response = $insuranceProviderAutomation->handle($this->process);
        info(__CLASS__.' fn:'.__FUNCTION__.' - Quote Code '.$this->process->model->code.' Response : '.json_encode($response));
    }

    public function failed(Throwable $exception)
    {
        $this->process->update(['status' => PolicyIssuanceEnum::FAILED_STATUS, 'message' => $exception->getMessage()]);

        Log::error(__CLASS__.' fn:'.__FUNCTION__.' - Quote Code '.$this->process->model->code.' Error : '.$exception->getMessage());
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->process->model->code))->dontRelease()];
    }
}
