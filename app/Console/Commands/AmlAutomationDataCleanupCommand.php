<?php

namespace App\Console\Commands;

use App\Enums\AmlAutomationStatus;
use App\Models\AmlAutomation;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AmlAutomationDataCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aml-screening-automation:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete completed aml screening automation record that are created 30 days ago';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = Carbon::now()->subDays(30);

        AmlAutomation::where('status', AmlAutomationStatus::COMPLETE_STATUS)
            ->where('created_at', '<', $date)
            ->delete();

        LoggerService::info('Aml Automation Data Clean Up Command executed successfully, data before date '.$date->format('Y-m-d H:i:s'));
    }
}
