<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\PaymentProcessJobEnum;
use App\Jobs\ProcessCCPaymentJob;
use App\Models\ApplicationStorage;
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

        $processCcPaymentsEnabled = ApplicationStorage::where('key_name', ApplicationStorageEnums::PROCESS_CC_PAYMENTS_ENABLED)->first();

        if ($processCcPaymentsEnabled?->value) {

            info('CC Payments Job ProcessCcPayments Enabled');

            CcPaymentProcess::where('status', PaymentProcessJobEnum::PENDING)
                ->chunk(100, function ($pendingCCRecords) {
                    foreach ($pendingCCRecords as $pendingCCRecord) {
                        $pendingCCRecord->update(['status' => PaymentProcessJobEnum::QUEUED]);
                        info('cc payment record : '.$pendingCCRecord->id.' queued');
                        ProcessCCPaymentJob::dispatch($pendingCCRecord->id);
                        info('Child payment code: '.$pendingCCRecord->splitPayment->code.' CC Payments Job ProcessCcPayments dispatched');
                    }
                });
        } else {
            info('CC Payments Job ProcessCcPayments are disabled');
        }

        info('CC Payments Job Ended');

        return 0;
    }
}
