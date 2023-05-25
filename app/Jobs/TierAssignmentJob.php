<?php

namespace App\Jobs;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\CarQuote;
use App\Services\ApplicationStorageService;
use App\Services\LeadAllocationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TierAssignmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $timeout = 300;
    public $backoff = 3;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ApplicationStorageService $applicationStorageService, LeadAllocationService $leadAllocationService)
    {
        $from = $applicationStorageService->getValueByKey('CAR_LEAD_ALLOCATION_START_DATE_FOR_LEADS');

        $weekBeforeDateTime = now()->subWeek(1)->startOfDay();

        if (Carbon::parse($from)->startOfDay() < $weekBeforeDateTime) {
            $from = $weekBeforeDateTime;
        }

        $isFIFO = $applicationStorageService->getValueByKey('CAR_LEAD_PICKUP_FIFO');

        $to = now()->subMinutes(2)->toDateTimeString();

        $carLeads = CarQuote::whereNull('tier_id')
            ->whereBetween('created_at', [$from, $to])
            ->where('quote_status_id', '!=', QuoteStatusEnum::Fake)
            ->whereNotIn('source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD])
            ->orderBy('created_at', $isFIFO ? 'asc' : 'desc')->get();

        foreach ($carLeads as $carLead) {

            $tier = $leadAllocationService->getTierForValue($carLead);

            if ($tier != null) {

                info('Found tier '.$tier->name.' against car lead : '.$carLead->code);

                $carLead->tier_id = $tier->id;

                $carLead->save();
            } else {

                info('No tier found to car lead : '.$carLead->code);
            }
        }
    }
}
