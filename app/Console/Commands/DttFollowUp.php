<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Jobs\Revival\CarRevivalFollowUpEmailJob;
use App\Models\DttRevival;
use App\Services\ApplicationStorageService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Sammyjo20\LaravelHaystack\Models\Haystack;

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
        $isDttEnabled = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::DTT_ENABLED);
        if ($isDttEnabled == false || $isDttEnabled == 0) {
            info('Dtt is not enabled from cms');

            return false;
        }

        $twoDaysBefore = Carbon::now()->subDays(2)->toDateString();
        $sevenDaysBefore = Carbon::now()->subDays(7)->toDateString();
        $thirteenDaysBefore = Carbon::now()->subDays(13)->toDateString();
        $twentyDaysBefore = Carbon::now()->subDays(20)->toDateString();
        $twentyEightDaysBefore = Carbon::now()->subDays(28)->toDateString();

        $unreplied = DttRevival::where(function ($q) use ($twoDaysBefore, $sevenDaysBefore, $thirteenDaysBefore, $twentyDaysBefore, $twentyEightDaysBefore) {
            $q->whereDate('created_at', '=', $twoDaysBefore);
            $q->orWhereDate('created_at', '=', $sevenDaysBefore);
            $q->orWhereDate('created_at', '=', $thirteenDaysBefore);
            $q->orWhereDate('created_at', '=', $twentyDaysBefore);
            $q->orWhereDate('created_at', '=', $twentyEightDaysBefore);
        })->where('reply_received', 0)
            ->get();

        $logPrefix = 'carRevivalFollowUpEmailJob -';

        $jobs = [];
        foreach ($unreplied as $item) {
            $jobs[] = new CarRevivalFollowUpEmailJob($item);
        }

        if ($jobs != null && count($jobs)) {
            Haystack::build()
                ->addJobs($jobs)

                ->then(function () use ($logPrefix) {
                    info($logPrefix.' all jobs completed successfully');
                })
                ->catch(function () use ($logPrefix) {
                    info($logPrefix.' one of batch is failed.');
                })
                ->finally(function () use ($logPrefix) {
                    info($logPrefix.' everything done');
                })
                ->allowFailures()
                ->withDelay(10)
                ->dispatch();
        } else {
            info($logPrefix.'No lead Found');
        }
    }
}
