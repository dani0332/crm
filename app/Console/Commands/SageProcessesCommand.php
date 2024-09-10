<?php

namespace App\Console\Commands;

use App\Enums\SageEnum;
use App\Jobs\BookPolicyOnSageJob;
use App\Models\SageProcess;
use Illuminate\Console\Command;

class SageProcessesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sage-processes:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process Sage Policy Booking single request per Insurance Provider';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sage Policy Booking Command Started');

        $insuranceProvidersProcessingStatus = SageProcess::where('status', SageEnum::SAGE_PROCESS_PROCESSING_STATUS)->pluck('insurance_provider_id')->toArray();

        $insuranceProviders = SageProcess::where('status', SageEnum::SAGE_PROCESS_PENDING_STATUS)
            ->whereNotIn('insurance_provider_id', $insuranceProvidersProcessingStatus)
            ->distinct()
            ->pluck('insurance_provider_id')->toArray();

        foreach ($insuranceProviders as $insuranceProvider) {
            $sageProcess = SageProcess::where('insurance_provider_id', $insuranceProvider)
                ->where('status', SageEnum::SAGE_PROCESS_PENDING_STATUS)
                ->orderBy('created_at')
                ->first();

            $sageProcessRequest = json_decode($sageProcess->request);
            $sageRequest = $sageProcessRequest->sagePayload;
            $request = $sageProcessRequest->requestPayload;

            if ($sageRequest->sageProcessRequestType == SageEnum::SAGE_PROCESS_BOOK_POLICY_REQUEST) {
                $quote = $sageProcess->model;
                BookPolicyOnSageJob::dispatch($sageRequest, $quote, $request, $sageProcess)->onQueue('sage-book-policy');
            }

        }
        $this->info('Sage Policy Booking Command Ended');
    }
}
