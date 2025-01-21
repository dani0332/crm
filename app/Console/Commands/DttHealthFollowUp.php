<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTypeId;
use App\Jobs\Revival\HealthRevivalFollowUpEmailJob;
use App\Models\DttRevival;
use App\Services\ApplicationStorageService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Sammyjo20\LaravelHaystack\Models\Haystack;

class DttHealthFollowUp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'DttHealthFollowUp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This cron will send follow-up email to customer when revival email is not replied OR lead is not assigned';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDttEnabled = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::DTT_HEALTH_ENABLED);
        if ($isDttEnabled == false || $isDttEnabled == 0) {
            info('DTT_HEALTH is not enabled from cms');

            return false;
        }

        $today = Carbon::today();

        $twoDaysBefore = Carbon::now()->subDays(2)->toDateString();
        $fiveDaysBefore = Carbon::now()->subDays(5)->toDateString();
        $eightDaysBefore = Carbon::now()->subDays(8)->toDateString();
        $twelveDaysBefore = Carbon::now()->subDays(12)->toDateString();
        $sixteenDaysBefore = Carbon::now()->subDays(16)->toDateString();
        $twentyDaysBefore = Carbon::now()->subDays(20)->toDateString();

        $fourDaysBefore = Carbon::now()->subDays(4)->toDateString();
        $sixDaysBefore = Carbon::now()->subDays(6)->toDateString();

        $logPrefix = 'DttHealthFollowUp - ';
        // dd('DTTFollowup date: '.$twoDaysBefore.'-----'.$fiveDaysBefore.'------'.$eightDaysBefore.'------'.$twelveDaysBefore.'------'.$sixteenDaysBefore.'------'.$twentyDaysBefore.'-');
        // dd('DTTFollowup date: ' . $twoDaysBefore . '-----' . $fourDaysBefore . '------' . $sixDaysBefore);

        $unrepliedWithPreviousPlantype = DttRevival::select('uuid')->where(function ($q) use ($twoDaysBefore, $fiveDaysBefore, $eightDaysBefore, $twelveDaysBefore, $sixteenDaysBefore, $twentyDaysBefore) {
            $q->whereDate('created_at', '=', $twoDaysBefore);
            $q->orWhereDate('created_at', '=', $fiveDaysBefore);
            $q->orWhereDate('created_at', '=', $eightDaysBefore);
            $q->orWhereDate('created_at', '=', $twelveDaysBefore);
            $q->orWhereDate('created_at', '=', $sixteenDaysBefore);
            $q->orWhereDate('created_at', '=', $twentyDaysBefore);
        })->where('reply_received', 0)->where('quote_type_id', QuoteTypeId::Health)->where('previous_health_plan_type', 1)->where('is_active', true)->get();

        $unrepliedWithoutPreviousPlantype = DttRevival::select('uuid')->where(function ($q) use ($twoDaysBefore, $fourDaysBefore, $sixDaysBefore) {
            $q->whereDate('created_at', '=', $twoDaysBefore);
            $q->orWhereDate('created_at', '=', $fourDaysBefore);
            $q->orWhereDate('created_at', '=', $sixDaysBefore);
        })->where('reply_received', 0)->where('quote_type_id', QuoteTypeId::Health)->where('previous_health_plan_type', 0)->where('is_active', true)->get();

        $revivalLeads = collect();
        // email payload with  previous health plan type
        foreach ($unrepliedWithPreviousPlantype as $item) {

            if ($today->isWeekend()) {

                info($logPrefix.' today is Weekend UUID: '.$item->uuid);

                $created_at = Carbon::parse($item->created_at)->addDays(1);
                DttRevival::where('uuid', $item->uuid)->update(['created_at' => $created_at]);

                continue;
            }
            $revivalLeads->push(['uuid' => $item->uuid, 'type' => 'unrepliedWithPreviousPlantype']);

        }

        // email payload without previous health plan type
        foreach ($unrepliedWithoutPreviousPlantype as $item) {

            if ($today->isWeekend()) {

                info($logPrefix.' today is Weekend UUID: '.$item->uuid);

                $created_at = Carbon::parse($item->created_at)->addDays(1);
                DttRevival::where('uuid', $item->uuid)->update(['created_at' => $created_at]);

                continue;
            }

            $revivalLeads->push(['uuid' => $item->uuid, 'type' => 'unrepliedWithoutPreviousPlantype']);

        }

        info($logPrefix.'count - '.$revivalLeads->count().' - leads - '.$revivalLeads->pluck('uuid')->toJson());

        $jobs = [];
        foreach ($revivalLeads as $item) {
            $jobs[] = new HealthRevivalFollowUpEmailJob($item['uuid'], $item['type']);
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
            info($logPrefix.'No HealthRevivalFollowUpEmailJobs Found');
        }
    }
}
