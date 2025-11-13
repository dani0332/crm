<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\EnvEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PaymentProcessJobEnum;
use App\Jobs\ProcessCCPaymentJob;
use App\Models\ApplicationStorage;
use App\Models\CcPaymentProcess;
use App\Services\Logger\LoggerService;
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
        LoggerService::startFeatureLogging(LoggerFeatureEnum::CC_PAYMENT_PROCESS_COMMAND);

        $environment = app()->environment();

        LoggerService::info("CC Payments Process Command Job Started in {$environment}");

        // Only enable in non-production environments
        if (! in_array($environment, [EnvEnum::PRODUCTION, EnvEnum::TEST, EnvEnum::STAGING, EnvEnum::DEVELOPMENT])) {
            LoggerService::info("CC Payments Process Command Job Disabled in {$environment}");

            return 0;
        }

        $processCcPaymentsEnabled = ApplicationStorage::where('key_name', ApplicationStorageEnums::PROCESS_CC_PAYMENTS_ENABLED)->first();

        if (! $processCcPaymentsEnabled?->value) {
            LoggerService::info("CC Payments Process Command Job Disabled in {$environment}");

            return 0;
        }

        LoggerService::info("CC Payments Process Command Job Enabled in {$environment}");

        CcPaymentProcess::where('status', PaymentProcessJobEnum::PENDING)
            ->chunk(100, function ($pendingCCRecords) use ($environment) {

                LoggerService::info("Processing {$pendingCCRecords->count()} pending CC payments in {$environment}");

                foreach ($pendingCCRecords as $pendingCCRecord) {
                    $splitPayment = $pendingCCRecord->splitPayment;
                    $splitPaymentCode = $splitPayment->code;

                    LoggerService::info("Queueing CC payment for split code: {$splitPaymentCode} ({$environment})");
                    $pendingCCRecord->update(['status' => PaymentProcessJobEnum::QUEUED]);
                    LoggerService::info("CC payment queued for split: {$splitPaymentCode} ({$environment})");

                    ProcessCCPaymentJob::dispatch($pendingCCRecord->id, $splitPayment->code);
                    LoggerService::info("Dispatched CC payment job for split: {$splitPaymentCode} ({$environment})");
                }
            });

        LoggerService::info("CC Payments Command completed in {$environment}");

        return 0;
    }
}
