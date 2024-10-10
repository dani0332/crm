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
    protected $description = 'mark sage processes as failed if they are processing for more than 5 minutes based on updated_at';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        info('cmd:SageProcessesMarkFailedCommand : Started');

        $fiveMinutesAgo = Carbon::now()->subMinutes(5);
        $sageProcesses = SageProcess::where('updated_at', '<', $fiveMinutesAgo)->where('status', SageEnum::SAGE_PROCESS_PROCESSING_STATUS)->get();

        foreach ($sageProcesses as $sageProcess) {

            (new SageApiService)->updateSageProcessStatus($sageProcess, SageEnum::SAGE_PROCESS_FAILED_STATUS, null, 'cmd:SageProcessesMarkFailedCommand');

            $sageProcessRequest = json_decode($sageProcess->request);
            $sageRequest = $sageProcessRequest->sagePayload;
            $request = $sageProcessRequest->requestPayload;

            if ($sageRequest->sageProcessRequestType == SageEnum::SAGE_PROCESS_BOOK_POLICY_REQUEST) {
                $quote = $this->getQuoteObject($request->model_type, $sageProcess->model_id);
                $quote->update([
                    'quote_status_id' => QuoteStatusEnum::POLICY_BOOKING_FAILED,
                    'quote_status_date' => now(),
                ]);
                info('cmd:SageProcessesMarkFailedCommand : updated quote status to ', ['Quote Code' => $quote->code, 'quote_status_id' => QuoteStatusEnum::POLICY_BOOKING_FAILED]);
            } elseif ($sageRequest->sageProcessRequestType == SageEnum::SAGE_PROCESS_SEND_UPDATE_REQUEST) {
                $model = $sageProcess->model;
                $model->update(['status' => SendUpdateLogStatusEnum::UPDATE_BOOKING_FAILED]);
                info('cmd:SageProcessesMarkFailedCommand : updated SendUpdate status to ', ['Send Update ID' => $model->id, 'status' => SendUpdateLogStatusEnum::UPDATE_BOOKING_FAILED]);
            }
        }

        info('cmd:SageProcessesMarkFailedCommand : Sage processes to update to failed status.', ['updated before' => $fiveMinutesAgo, 'Sage Processes Count' => $sageProcesses->count()]);

        info('cmd:SageProcessesMarkFailedCommand : ended');
    }
}
