<?php

namespace App\Console\Commands;

use App\Enums\PaymentProcessJobEnum;
use App\Jobs\ProcessCCPaymentJob;
use App\Models\CcPaymentProcess;
use Illuminate\Console\Command;

class ProcessCCPaymentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ProcessCCPaymentsCommand:cron';

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
        info('CC Payments Job Started');
        CcPaymentProcess::where('status', PaymentProcessJobEnum::PENDING)
            ->chunk(100, function ($pendingCCRecords) {
                foreach ($pendingCCRecords as $pendingCCRecord) {
                    ProcessCCPaymentJob::dispatch($pendingCCRecord);
                }
            });
        info('CC Payments Job Ended');

        return 0;
    }
}
