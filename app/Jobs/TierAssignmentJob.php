<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Mail\TierAssignmentFailedNotification;
use App\Models\CarQuote;
use App\Services\ApplicationStorageService;
use App\Services\LeadAllocationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TierAssignmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 2;
    public $timeout = 55;
    public $backoff = 20;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ApplicationStorageService $applicationStorageService, LeadAllocationService $leadAllocationService)
    {
        $currentIteration = now();

        info('------------------- Tier Assignment Job Started At : '.$currentIteration.' -------------------');

        $tierAssignmentSwitch = $applicationStorageService->getValueByKey(ApplicationStorageEnums::TIER_ASSIGNMENT_SWITCH);

        $masterSwitchConfigValue = (int) config('constants.TIER_ASSIGNMENT_MASTER_SWITCH');

        if ($tierAssignmentSwitch != 0 && $masterSwitchConfigValue != 0) {

            $from = $applicationStorageService->getValueByKey(ApplicationStorageEnums::TIER_ASSIGNMENT_PROCESS_START_DATE);

            $isFIFO = $applicationStorageService->getValueByKey(ApplicationStorageEnums::CAR_LEAD_PICKUP_FIFO);

            $to = now()->subMinutes(2)->toDateTimeString();

            $carLeads = CarQuote::whereNull('tier_id')
                ->whereBetween('created_at', [$from, $to])
                ->where('source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
                ->orderBy('created_at', $isFIFO ? 'asc' : 'desc')
                ->select('id', 'tier_id', 'cost_per_lead', 'code', 'is_ecommerce', 'car_type_insurance_id', 'car_value', 'source', 'uuid',
                    'previous_policy_expiry_date', 'email', 'mobile_no', 'car_make_id', 'car_model_id', 'is_renewal_tier_email_sent', 'created_at')
                ->skip(0)->take(1000)
                ->get();

            foreach ($carLeads as $carLead) {

                info('------------------- Processing Lead : '.$carLead->code.' -------------------');

                $tier = $leadAllocationService->getTierForValue($carLead);

                if ($tier != null) {

                    info('Tier : Assignment , found tier '.$tier->name.' against car lead : '.$carLead->code.' , uuid : '.$carLead->uuid);

                    $carLead->tier_id = $tier->id;

                    $carLead->cost_per_lead = $tier->cost_per_lead;

                    $carLead->save();

                    info('Tier : Assignment done '.$tier->name.' against car lead : '.$carLead->code.' , uuid : '.$carLead->uuid);
                } else {

                    info('No tier found to car lead : '.$carLead->code);
                }

            }

            info('------------------- Tier Assignment Job Finished for '.$currentIteration.' -------------------');

            return;

        } else {
            info('Tier Assignment Job is turned Off');
            info('------------------- Tier Assignment Job Finished for '.$currentIteration.' -------------------');

            return;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \App\Events\OrderShipped  $event
     * @return void
     */
    public function failed(Throwable $exception)
    {
        if ($exception) {
            Log::error('Exception in lead allocation : '.$exception->getMessage());
            Mail::send(new TierAssignmentFailedNotification($exception));
        }
    }
}
