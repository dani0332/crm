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
    protected $description = 'Process Sage Policy and Endorsements Booking single request per Insurance Provider';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        info('cmd:SageProcessesCommand - Policy Booking Started');

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

                info('cmd:SageProcessesCommand - Processing Sage Process ID: '.$sageProcess->id.' for Insurance Provider ID: '.$insuranceProvider);
                $sageProcessRequest = json_decode($sageProcess->request);
                $sageRequest = $sageProcessRequest->sagePayload;
                $request = $sageProcessRequest->requestPayload;

                if ($sageRequest->sageProcessRequestType == SageEnum::SAGE_PROCESS_BOOK_POLICY_REQUEST) {
                    $quote = $sageProcess->model;
                    BookPolicyOnSageJob::dispatch($sageRequest, $quote, $request, $sageProcess)->onQueue('sage-processes');
                }

                if ($sageRequest->sageProcessRequestType == SageEnum::SAGE_PROCESS_SEND_UPDATE_REQUEST) {
                    $quote = $sageProcess->model;
                    SendUpdateSageJob::dispatch($request, $quote, $sageRequest, $sageProcess)->onQueue('sage-processes');
                }
            }
        } else {
            info('cmd:SageProcessesCommand - No Sage Process meet the selection criteria / already sage processes are being processed against all insurance providers');
        }

        info('cmd:SageProcessesCommand - Sage Policy Booking Command Ended');
    }

}
