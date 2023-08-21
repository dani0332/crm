<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Models\CarQuote;
use App\Services\ApplicationStorageService;
use App\Services\LeadAllocationService;
use Illuminate\Console\Command;

class TierAssignment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'TierAssignment:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tier Assignment cron';

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
    public function handle(ApplicationStorageService $applicationStorageService, LeadAllocationService $leadAllocationService)
    {
        $currentIteration = now();

        info('------------------- Tier Assignment Command Started At : '.$currentIteration.' -------------------');

        $tierAssignmentSwitch = $applicationStorageService->getValueByKey(ApplicationStorageEnums::TIER_ASSIGNMENT_SWITCH);

        $masterSwitchConfigValue = (int) config('constants.TIER_ASSIGNMENT_MASTER_SWITCH');

        if ($tierAssignmentSwitch != 0 && $masterSwitchConfigValue != 0) {
            $from = now()->subDays(2)->startOfDay()->toDateTimeString();

            $isFIFO = $applicationStorageService->getValueByKey(ApplicationStorageEnums::CAR_LEAD_PICKUP_FIFO);

            $to = now()->subMinutes(2)->toDateTimeString();

            $chunkSize = 50;

            CarQuote::whereNull('tier_id')
                ->whereBetween('created_at', [$from, $to])
                ->where('source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
                ->orderBy('created_at', $isFIFO ? 'asc' : 'desc')
                ->select(
                    'id',
                    'tier_id',
                    'cost_per_lead',
                    'code',
                    'is_ecommerce',
                    'car_type_insurance_id',
                    'car_value',
                    'source',
                    'uuid',
                    'previous_policy_expiry_date',
                    'email',
                    'mobile_no',
                    'car_make_id',
                    'car_model_id',
                    'is_renewal_tier_email_sent',
                    'created_at'
                )
                ->chunk($chunkSize, function ($carLeads) use ($leadAllocationService) {
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
                });
        } else {
            info('Tier Assignment Command is turned Off');
            info('------------------- Tier Assignment Command Finished for '.$currentIteration.' -------------------');

            return;
        }
    }
}
