<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ProcessCCPaymentsJob;

use App\Models\CcPaymentProcessJob;
use App\Services\SplitPaymentService;
use App\Enums\PaymentProcessJobEnum;

class RunProcessCCPaymentsJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:run-process-c-c-payments-job {paymentProcessJobId?}';

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
        $paymentProcessJobId = $this->argument('paymentProcessJobId');
        if($paymentProcessJobId){
            $paymentProcessJob = CcPaymentProcessJob::find($paymentProcessJobId);
            if($paymentProcessJob){
                info("Manual CC Payments Job Started For Payment Split ID: ".$paymentProcessJob->payment_splits_id);
                CcPaymentProcessJob::where('payment_splits_id',$paymentProcessJob->payment_splits_id)->update(['status' => PaymentProcessJobEnum::INPROCESS_STATUS]);
                app(SplitPaymentService::class)->processSplitPaymentApprove($paymentProcessJob->model_type,$paymentProcessJob->quote_id, $paymentProcessJob->payment_splits_id, $paymentProcessJob->amount_captured,true);
                info("Manual CC Payments Job Ended For Payment Split ID: ".$paymentProcessJob->payment_splits_id);
            }
        } else {
            $pendingCCRecords = CcPaymentProcessJob::where('status',PaymentProcessJobEnum::PENDING_STATUS)->get();
            if($pendingCCRecords->count() > 0){            
                foreach($pendingCCRecords as $pendingCCRecord){
                    info("CC Payments Job Started For Payment Split ID: ".$pendingCCRecord->payment_splits_id);
                    CcPaymentProcessJob::where('payment_splits_id',$pendingCCRecord->payment_splits_id)->update(['status' => PaymentProcessJobEnum::INPROCESS_STATUS]);
                    app(SplitPaymentService::class)->processSplitPaymentApprove($pendingCCRecord->model_type,$pendingCCRecord->quote_id, $pendingCCRecord->payment_splits_id, $pendingCCRecord->amount_captured,true);
                    info("CC Payments Job Ended For Payment Split ID: ".$pendingCCRecord->payment_splits_id);
                }
            }
        }
        info("CC Payments Job Started");
        ////ProcessCCPaymentsJob::dispatch();
        $this->info('ProcessCCPaymentsJob has been dispatched.');
        return 0;
    }
}
