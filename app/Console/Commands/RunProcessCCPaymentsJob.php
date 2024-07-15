<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ProcessCCPaymentsJob;

use App\Models\CcPaymentProcessJob;
use App\Services\SplitPaymentService;
use App\Enum\PaymentProcessJobEnum;

class RunProcessCCPaymentsJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:run-process-c-c-payments-job';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to process CC payments';

    /**
     * Execute the console command.
     */
    public function handle()
    {   
        info("CC Payments Job Started");
        $pendingCCRecords = CcPaymentProcessJob::where('status',PaymentProcessJobEnum::PENDING_STATUS)->get();
        if($pendingCCRecords->count() > 0){            
            foreach($pendingCCRecords as $pendingCCRecord){
                info("CC Payments Job Started For Payment Split ID: ".$pendingCCRecord->payment_split_id);
                CcPaymentProcessJob::where('payment_split_id',$pendingCCRecord->payment_split_id)->update(['status' => PaymentProcessJobEnum::INPROCESS_STATUS]);
                app(SplitPaymentService::class)->processSplitPaymentApprove($pendingCCRecord->model_type,$pendingCCRecord->quote_id, $pendingCCRecord->payment_split_id, $pendingCCRecord->amount_captured,true);
                info("CC Payments Job Ended For Payment Split ID: ".$pendingCCRecord->payment_split_id);
            }
        }
        info("CC Payments Job Started");
        ////ProcessCCPaymentsJob::dispatch();
        $this->info('ProcessCCPaymentsJob has been dispatched.');
        return 0;
    }
}
