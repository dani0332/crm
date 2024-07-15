<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\CcPaymentProcessJob;
use App\Services\SplitPaymentService;
use App\Enum\PaymentProcessJobEnum;


class ProcessCCPaymentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $pendingCCRecords = CcPaymentProcessJob::where('status','pending')->get();
        if($pendingCCRecords->count() > 0){            
            foreach($pendingCCRecords as $pendingCCRecord){
                CcPaymentProcessJob::where('payment_split_id',$pendingCCRecord->payment_split_id)->update(['status' => 'in-process']);
                app(SplitPaymentService::class)->processSplitPaymentApprove($pendingCCRecord->model_type,$pendingCCRecord->quote_id, $pendingCCRecord->payment_split_id, $pendingCCRecord->amount_captured,true);
            }
        }
    }
}
