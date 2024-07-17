<?php

namespace App\Console\Commands;

use App\Enums\PaymentProcessJobEnum;
use App\Models\CcPaymentProcessJob;
use App\Services\SplitPaymentService;
use Illuminate\Console\Command;

class RunProcessCCPaymentsJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'RunProcessCCPaymentsJob:cron';

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

        CcPaymentProcessJob::where('status', PaymentProcessJobEnum::PENDING_STATUS)
            ->chunk(100, function ($pendingCCRecords) {
                foreach ($pendingCCRecords as $pendingCCRecord) {
                    info("CC Payments Job Started For Payment Split ID: {$pendingCCRecord->payment_splits_id}");

                    try {
                        $pendingCCRecord->update(['status' => PaymentProcessJobEnum::INPROCESS_STATUS]);

                        app(SplitPaymentService::class)->processSplitPaymentApprove(
                            $pendingCCRecord->model_type,
                            $pendingCCRecord->quoteable_id,
                            $pendingCCRecord->payment_splits_id,
                            $pendingCCRecord->amount_captured,
                            true
                        );

                        info("CC Payments Job Ended For Payment Split ID: {$pendingCCRecord->payment_splits_id}");
                    } catch (\Exception $exception) {
                        // Handle the exception here
                        info("CC Payment Processing Failed for Payment Split ID: {$pendingCCRecord->payment_splits_id} - Error: ".$exception->getMessage());
                    }
                }
            });

        info('CC Payments Job Ended');

        return 0;
    }
}
