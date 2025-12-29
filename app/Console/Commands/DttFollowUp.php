<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Jobs\Revival\CarRevivalFollowUpEmailJob;
use App\Models\DttRevival;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class DttFollowUp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Dtt:followup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This cron will send follow-up email to customer when revival email is not replied OR lead is not assigned';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $isDttEnabled = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::DTT_ENABLED);
            if ($isDttEnabled == false || $isDttEnabled == 0) {
                LoggerService::info('Dtt is not enabled from cms');

                return false;
            }

            $twoDaysBefore = Carbon::now()->subDays(2)->toDateString();
            $sevenDaysBefore = Carbon::now()->subDays(7)->toDateString();
            $thirteenDaysBefore = Carbon::now()->subDays(13)->toDateString();
            $twentyDaysBefore = Carbon::now()->subDays(20)->toDateString();
            $twentyEightDaysBefore = Carbon::now()->subDays(28)->toDateString();

            $logPrefix = 'carRevivalFollowUpEmailJob -';

            LoggerService::info($logPrefix.' Starting command execution');

            // Use chunk to prevent memory issues with large datasets
            $totalJobs = 0;
            $unreplied = DttRevival::where(function ($q) use ($twoDaysBefore, $sevenDaysBefore, $thirteenDaysBefore, $twentyDaysBefore, $twentyEightDaysBefore) {
                $q->whereDate('created_at', '=', $twoDaysBefore);
                $q->orWhereDate('created_at', '=', $sevenDaysBefore);
                $q->orWhereDate('created_at', '=', $thirteenDaysBefore);
                $q->orWhereDate('created_at', '=', $twentyDaysBefore);
                $q->orWhereDate('created_at', '=', $twentyEightDaysBefore);
            })->where('reply_received', 0);

            $unrepliedCount = $unreplied->count();

            if ($unrepliedCount === 0) {
                LoggerService::info($logPrefix.' No leads found');

                return false;
            }

            LoggerService::info($logPrefix." Found {$unrepliedCount} leads to process");

            $jobs = [];
            $delayCounter = 0;

            // Process in chunks to avoid memory issues
            $unreplied->chunk(100, function ($items) use (&$jobs, &$delayCounter, &$totalJobs) {
                foreach ($items as $item) {
                    $jobs[] = (new CarRevivalFollowUpEmailJob($item))->delay(now()->addSeconds(10 + $delayCounter));
                    $delayCounter += 10;
                    $totalJobs++;
                }
            });

            if (count($jobs) > 0) {
                LoggerService::info($logPrefix." Dispatching {$totalJobs} jobs");

                Bus::batch($jobs)
                    ->then(function () use ($logPrefix, $totalJobs) {
                        LoggerService::info($logPrefix." all {$totalJobs} jobs completed successfully");
                    })
                    ->catch(function (\Throwable $e) use ($logPrefix) {
                        LoggerService::info($logPrefix.' one of batch failed', extra: [
                            'error' => $e->getMessage(),
                        ]);
                    })
                    ->finally(function () use ($logPrefix) {
                        LoggerService::info($logPrefix.' everything done');
                    })
                    ->allowFailures()
                    ->name('Car Revival Follow Up Email Jobs')
                    ->dispatch();

                LoggerService::info($logPrefix.' Command completed successfully');

                return true;
            }

            LoggerService::info($logPrefix.' No jobs to dispatch');

            return false;
        } catch (\Throwable $e) {
            LoggerService::warning('Dtt:followup command failed', extra: [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
