<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
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

    public $tries = 3;
    public $timeout = 300;
    public $backoff = 3;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ApplicationStorageService $applicationStorageService, LeadAllocationService $leadAllocationService)
    {
        $tierAssignmentSwitch = $applicationStorageService->getValueByKey(ApplicationStorageEnums::TIER_ASSIGNMENT_SWITCH);

        if ($tierAssignmentSwitch != 0) {

            $from = $applicationStorageService->getValueByKey(ApplicationStorageEnums::TIER_ASSIGNMENT_PROCESS_START_DATE);

            // $weekBeforeDateTime = now()->subWeek(1)->startOfDay();

            // if (Carbon::parse($from)->startOfDay() < $weekBeforeDateTime) {
            //     $from = $weekBeforeDateTime;
            // }

            $isFIFO = $applicationStorageService->getValueByKey(ApplicationStorageEnums::CAR_LEAD_PICKUP_FIFO);

            $to = now()->subMinutes(2)->toDateTimeString();

            $carLeads = CarQuote::whereNull('tier_id')
                ->whereBetween('created_at', [$from, $to])
                ->orderBy('created_at', $isFIFO ? 'asc' : 'desc')
                ->select('id', 'tier_id', 'cost_per_lead', 'code', 'is_ecommerce', 'car_type_insurance_id', 'car_value', 'source')
                ->skip(0)->take(3000)
                ->get();

            foreach ($carLeads as $carLead) {

                $tier = $leadAllocationService->getTierForValue($carLead);

                if ($tier != null) {

                    info('Tier : Assignment , found tier '.$tier->name.' against car lead : '.$carLead->code. ' , uuid : '. $carLead->uuid);

                    $carLead->tier_id = $tier->id;

                    $carLead->cost_per_lead = $tier->cost_per_lead;

                    $carLead->save();
                } else {

                    info('No tier found to car lead : '.$carLead->code);
                }
            }

        } else {
            info('Tier Assignment Job is turned Off');
        }
    }
}
