<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Jobs\Revival\CarRevivalFollowUpEmailJobOld;
use App\Models\DttRevival;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class DttFollowUp extends Command
{
    private const LEGACY_FOLLOW_UP_CUTOFF_DATE = '2026-04-29';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Dtt:followup {--date= : Anchor date (Y-m-d) for follow-up windows; defaults to now when omitted}';

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

                return Command::SUCCESS;
            }

            $dateOption = $this->option('date');
            $carbon = filled($dateOption)
                ? Carbon::parse((string) $dateOption)->startOfDay()
                : Carbon::now();

            $followUpAnchorDate = filled($dateOption)
                ? Carbon::parse((string) $dateOption)->toDateString()
                : null;

            $twoDaysBefore = $carbon->copy()->subDays(2)->toDateString();
            $sevenDaysBefore = $carbon->copy()->subDays(7)->toDateString();
            $thirteenDaysBefore = $carbon->copy()->subDays(13)->toDateString();
            $twentyDaysBefore = $carbon->copy()->subDays(20)->toDateString();
            $twentyEightDaysBefore = $carbon->copy()->subDays(28)->toDateString();

            $logPrefix = 'carRevivalFollowUpEmailJob -';

            $jobs = [];
            $delayCounter = 0;

            // Use chunking to avoid memory issues with large datasets
            DttRevival::where(function ($q) use ($twoDaysBefore, $sevenDaysBefore, $thirteenDaysBefore, $twentyDaysBefore, $twentyEightDaysBefore) {
                $q->whereDate('created_at', '=', $twoDaysBefore);
                $q->orWhereDate('created_at', '=', $sevenDaysBefore);
                $q->orWhereDate('created_at', '=', $thirteenDaysBefore);
                $q->orWhereDate('created_at', '=', $twentyDaysBefore);
                $q->orWhereDate('created_at', '=', $twentyEightDaysBefore);
            })
                ->whereDate('created_at', '<=', self::LEGACY_FOLLOW_UP_CUTOFF_DATE)
                ->where('reply_received', 0)
                ->select('id', 'uuid') // Only select needed fields to reduce memory usage
                ->chunk(100, function ($unreplied) use (&$jobs, &$delayCounter, $followUpAnchorDate) {
                    foreach ($unreplied as $item) {
                        // Pass only the ID to avoid serialization issues with full model
                        $jobs[] = (new CarRevivalFollowUpEmailJobOld($item->id, $followUpAnchorDate))
                            ->delay(now()->addSeconds(10 + $delayCounter));
                        $delayCounter += 10;
                    }
                });

            if (! empty($jobs) || count($jobs) > 0) {
                Bus::batch($jobs)
                    ->then(function () use ($logPrefix) {
                        LoggerService::info($logPrefix.' all jobs completed successfully');
                    })
                    ->catch(function () use ($logPrefix) {
                        LoggerService::info($logPrefix.' one of batch is failed.');
                    })
                    ->finally(function () use ($logPrefix) {
                        LoggerService::info($logPrefix.' everything done');
                    })
                    ->allowFailures()
                    ->name('Car Revival Follow Up Email Jobs')
                    ->dispatch();
            } else {
                LoggerService::info($logPrefix.'No lead Found');
            }

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            LoggerService::warning('Dtt:followup command failed', extra: [
                'error' => $e->getMessage(),
            ]);

            return Command::SUCCESS;
        }
    }
}
