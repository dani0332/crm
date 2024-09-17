<?php

namespace App\Console\Commands;

use App\Enums\SageEnum;
use App\Jobs\BookPolicyOnSageJob;
use App\Jobs\SendUpdateSageJob;
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
        $this->info('cmd:SageProcessesCommand - Policy Booking Started');

        $insuranceProvidersProcessingStatus = SageProcess::where('status', SageEnum::SAGE_PROCESS_PROCESSING_STATUS)->pluck('insurance_provider_id')->toArray();

        $insuranceProviders = SageProcess::where('status', SageEnum::SAGE_PROCESS_PENDING_STATUS)
            ->whereNotIn('insurance_provider_id', $insuranceProvidersProcessingStatus)
            ->distinct()
            ->pluck('insurance_provider_id')->toArray();

        if (count($insuranceProviders) > 0) {
            foreach ($insuranceProviders as $insuranceProvider) {
                $sageProcess = SageProcess::where('insurance_provider_id', $insuranceProvider)
                    ->where('status', SageEnum::SAGE_PROCESS_PENDING_STATUS)
                    ->orderBy('created_at')
                    ->first();
                $this->info('cmd:SageProcessesCommand - Processing Sage Process ID: '.$sageProcess->id.' for Insurance Provider ID: '.$insuranceProvider);
                $sageProcessRequest = json_decode($sageProcess->request);
                $sageRequest = $sageProcessRequest->sagePayload;
                $request = $sageProcessRequest->requestPayload;

                if ($sageRequest->sageProcessRequestType == SageEnum::SAGE_PROCESS_BOOK_POLICY_REQUEST) {
                    $quote = $sageProcess->model;
                    BookPolicyOnSageJob::dispatch($sageRequest, $quote, $request, $sageProcess)->onQueue('sage-book-policy');
                }

                if ($sageRequest->sageProcessRequestType == SageEnum::SAGE_PROCESS_SEND_UPDATE_REQUEST) {
                    $preparedEndorsementData = $sageProcessRequest->endorsementPreparedData;
                    $quote = $sageProcess->model;
                    SendUpdateSageJob::dispatch($request, $quote, $sageRequest, $preparedEndorsementData, $sageProcess)->onQueue('sage-book-endorsement');
                }
            }
        } else {
            $this->info('cmd:SageProcessesCommand - No Sage Process meet the selection criteria / already sage processes are being processed against all insurance providers');
        }

        $this->info('cmd:SageProcessesCommand - Sage Policy Booking Command Ended');
    }
}
