<?php

namespace App\Console\Commands;

use App\Enums\QuoteStatusEnum;
use App\Enums\SageEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\SageProcess;
use App\Services\SageApiService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SageProcessesMarkFailedCommand extends Command
{
    use GenericQueriesAllLobs;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sage-processes:mark-failed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark the Sage process as failed, as it has been stuck in processing status for the last five minutes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        info('cmd:SageProcessesMarkFailedCommand Started');

        $fiveMinutesAgo = Carbon::now()->subMinutes(5);
        $sageProcesses = SageProcess::where('updated_at', '<', $fiveMinutesAgo)->where('status', SageEnum::SAGE_PROCESS_PROCESSING_STATUS)->get();
        
        info('cmd:SageProcessesMarkFailedCommand Sage processes to update to failed status.', ['updated before' => $fiveMinutesAgo, 'Sage Processes Count' => $sageProcesses->count()]);
        
        foreach ($sageProcesses as $sageProcess) {

            (new SageApiService)->updateSageProcessStatus($sageProcess, SageEnum::SAGE_PROCESS_FAILED_STATUS);

            $sageProcessRequest = json_decode($sageProcess->request);
            $sageRequest = $sageProcessRequest->sagePayload;
            $request = $sageProcessRequest->request;

            if ($sageRequest->sageProcessRequestType == SageEnum::SAGE_PROCESS_BOOK_POLICY_REQUEST) {
                $quote = $this->getQuoteObject($request->model_type, $sageProcess->model_id);
                $quote->update([
                    'quote_status_id' => QuoteStatusEnum::POLICY_BOOKING_FAILED,
                    'quote_status_date' => now(),
                ]);
                info('cmd:SageProcessesMarkFailedCommand updated quote status to failed', ['Quote Code' => $quote->code]);
            } elseif ($sageRequest->sageProcessRequestType == SageEnum::SAGE_PROCESS_SEND_UPDATE_REQUEST) {
                $model = $sageProcess->model;
                $model->update(['status' => SendUpdateLogStatusEnum::UPDATE_BOOKING_FAILED]);
                info('cmd:SageProcessesMarkFailedCommand updated SendUpdate status to failed', ['Send Update ID' => $model->id]);
            }

        }

        info('cmd:SageProcessesMarkFailedCommand ended');
    }
}
