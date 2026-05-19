<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Services\Logger\LoggerService;
use App\Services\Reports\ConversionOptimizationScheduledExportService;
use Illuminate\Console\Command;

class ReportsConversionOptimizationScheduledExportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:conversion-optimization-scheduled-export
                            {application-storage-key? : application_storage.key_name (JSON: to_email, cc_emails, batch, optional filters)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queue the Conversion Optimization report CSV email (scheduled in production; run manually elsewhere)';

    /**
     * Execute the console command.
     */
    public function handle(ConversionOptimizationScheduledExportService $scheduledExportService): int
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::CONVERSION_OPTIMIZATION_SCHEDULED_EXPORT);

        try {
            $storageKey = $this->argument('application-storage-key')
                ?: ApplicationStorageEnums::CONVERSION_OPTIMIZATION_SCHEDULED_EXPORT_PARAMS;

            LoggerService::info('reports:conversion-optimization-scheduled-export started', [
                'environment' => app()->environment(),
                'timezone' => config('app.timezone'),
                'application_storage_key' => $storageKey,
            ]);

            if ($scheduledExportService->dispatchScheduledExport($storageKey)) {
                $this->info('Conversion optimization export job dispatched.');

                return Command::SUCCESS;
            }

            $this->error('Conversion optimization export was not dispatched (see logs).');

            return Command::FAILURE;
        } finally {
            LoggerService::endLogging();
        }
    }
}
