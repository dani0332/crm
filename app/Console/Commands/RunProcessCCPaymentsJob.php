<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\JobsProcessCCPaymentsJob;

use App\Models\CcPaymentProcessJob;

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
       
        $pendingCCRecords = CcPaymentProcessJob::where('status','pending')->get();
        if($pendingCCRecords->count() > 0){            
            foreach($pendingCCRecords as $pendingCCRecord){
                app(SplitPaymentService::class)->processSplitPaymentApprove($pendingCCRecord->model_type,$pendingCCRecord->quote_id, $pendingCCRecord->payment_split_id, $pendingCCRecord->amount_captured,true);
            }
        }
        //ProcessCCPaymentsJob::dispatch();
        $this->info('ProcessCCPaymentsJob has been dispatched.');
        return 0;

    }
}
