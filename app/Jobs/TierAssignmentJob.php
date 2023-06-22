<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Mail\TierAssignmentFailedNotification;
use App\Models\CarQuote;
use App\Services\ApplicationStorageService;
use App\Services\LeadAllocationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TierAssignmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 117;
    public $backoff = 3;

    public function __construct()
    {
        $this->onQueue('lms');
    }

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

        if ($tierAssignmentSwitch != 0) {

            $from = $applicationStorageService->getValueByKey(ApplicationStorageEnums::TIER_ASSIGNMENT_PROCESS_START_DATE);

            // $weekBeforeDateTime = now()->subWeek(1)->startOfDay();

            // if (Carbon::parse($from)->startOfDay() < $weekBeforeDateTime) {
            //     $from = $weekBeforeDateTime;
            // }

            $isFIFO = $applicationStorageService->getValueByKey(ApplicationStorageEnums::CAR_LEAD_PICKUP_FIFO);

            $lmsStartDate = $applicationStorageService->getValueByKey(ApplicationStorageEnums::CAR_LEAD_ALLOCATION_START_DATE_FOR_LEADS);

            $to = now()->subMinutes(2)->toDateTimeString();

            $dateFormat = config('constants.DATETIME_DISPLAY_FORMAT');

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

                if ($leadAllocationService->checkIfLeadIsRenewal($carLead)) {
                    info('Renewal found against quote Id : '.$carLead->uuid);

                    $leadCreationDate = Carbon::parse($carLead->created_at);

                    if (! $carLead->is_renewal_tier_email_sent && $leadCreationDate->gt($lmsStartDate)) {

                        info('About to send Renewal Tier R email for quote Id : '.$carLead->uuid);

                        $lead = CarQuote::where('uuid', $carLead->uuid)->first();

                        CarRenewalEmailJob::dispatch($lead);
                    } else {
                        info('Renewal email not sent created_at for lead : '.$carLead->uuid.' is : '.$carLead->created_at.' and email flag for renewal is : '.$carLead->is_renewal_tier_email_sent);
                    }

                } else {
                    $tier = $leadAllocationService->getTierForValue($carLead);

                    if ($tier != null) {

                        info('Tier : Assignment , found tier '.$tier->name.' against car lead : '.$carLead->code.' , uuid : '.$carLead->uuid);

                        $carLead->tier_id = $tier->id;

                        $carLead->cost_per_lead = $tier->cost_per_lead;

                        $carLead->save();
                    } else {

                        info('No tier found to car lead : '.$carLead->code);
                    }
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
