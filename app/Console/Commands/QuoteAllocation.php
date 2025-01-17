<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\TeamNameEnum;
use App\Enums\TiersIdEnum;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\PersonalQuote;
use App\Models\TravelQuote;
use App\Services\ApplicationStorageService;
use Illuminate\Console\Command;

class QuoteAllocation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'QuoteAllocation:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This runs to check if any unassigned is available then assign them accordingly';

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
    public function handle(ApplicationStorageService $applicationStorageService)
    {
        $currentIteration = now();
        info("------------------- Quote Allocation Command Started At: $currentIteration -------------------");

        $quoteAllocationSwitch = $applicationStorageService->getValueByKey(ApplicationStorageEnums::QUOTE_ALLOCATION_SWITCH);
        $masterSwitchConfigValue = (int) config('constants.QUOTE_ALLOCATION_MASTER_SWITCH');
        $allocationStartDate = now()->subWeek()->startOfDay()->toDateTimeString();
        if ($quoteAllocationSwitch == 1 && $masterSwitchConfigValue == 1) {
            $to = now()->subMinutes(5)->toDateTimeString();
            $chunkSize = 200;
            info('start and end dates are : '.$allocationStartDate.' and '.$to);
            $this->executeCarAllocation(QuoteTypeId::Car, $to, $chunkSize, $allocationStartDate, $applicationStorageService);
            $this->executeHealthAllocation(QuoteTypeId::Health, $to, $chunkSize, $allocationStartDate);
            $this->executeBikeAllocation(QuoteTypeId::Bike, $to, $chunkSize, $allocationStartDate, $applicationStorageService);
            $this->executeTravelAllocation(QuoteTypeId::Travel, $to, $chunkSize, $allocationStartDate);

            // Disabled Auto Allocation for now as this feature is not needed at the moment
            /*
            $this->executeAllocation(QuoteTypes::CORPLINE, $to, $chunkSize, $allocationStartDate);
            $this->executeAllocation(QuoteTypes::CYCLE, $to, $chunkSize, $allocationStartDate);
            $this->executeAllocation(QuoteTypes::PET, $to, $chunkSize, $allocationStartDate);
            $this->executeAllocation(QuoteTypes::YACHT, $to, $chunkSize, $allocationStartDate);
            $this->executeAllocation(QuoteTypes::LIFE, $to, $chunkSize, $allocationStartDate);
            $this->executeAllocation(QuoteTypes::HOME, $to, $chunkSize, $allocationStartDate);
            */
        } else {
            info('Quote Allocation Command is turned Off');
        }

        info("------------------- Quote Allocation Command Finished for $currentIteration -------------------");
    }

    public function executeCarAllocation($quoteType, $to, $chunkSize, $allocationStartDate, $applicationStorageService)
    {
        $processedRecords = 0;
        $shouldIncludeDubaiNow = $applicationStorageService->getValueByKey(ApplicationStorageEnums::APPLY_DUBAI_NOW_EXCLUSION) == 1;
        $exemptedLeadSources = [LeadSourceEnum::IMCRM, LeadSourceEnum::INSLY, LeadSourceEnum::REVIVAL];

        if ($shouldIncludeDubaiNow) {
            $exemptedLeadSources[] = LeadSourceEnum::DUBAI_NOW;
        }

        $leads = CarQuote::whereNull('advisor_id')
            ->select('uuid', 'payment_status_id', 'source', 'is_renewal_tier_email_sent', 'lead_allocation_failed_at', 'sic_flow_enabled', 'sic_advisor_requested', 'quote_status_id')
            ->where('created_at', '<=', $to)
            ->orderBy('created_at', 'desc')
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('source', $exemptedLeadSources)
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('source', LeadSourceEnum::RENEWAL_UPLOAD)->sicFlowEnabled()->requestedAdvisorOrPaymentAuthorized();
                })
                    ->orWhere->leadAllocationFailed()
                    ->orWhere(function ($query) {
                        $query->where('source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)
                            ->where(function ($sq) {
                                $sq->where(function ($q) {
                                    $q->sicFlowDisabled();
                                })->orWhere(function ($query) {
                                    $query->sicFlowEnabled()->requestedAdvisorOrPaymentAuthorized();
                                });
                            });
                    });
            })
            ->take($chunkSize);

        info('leads fetch query is : '.$leads->toRawSql());

        // Get the teamId once before the loop
        $teamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);

        foreach ($leads->get() as $lead) {
            if ($lead->tier_id == TiersIdEnum::TIER_R) {
                continue;
            }

            info('Processing record for Quote Allocation with uuid: '.$lead->uuid, [
                'uuid' => $lead->uuid,
                'payment_status_id' => $lead->payment_status_id,
                'source' => $lead->source,
                'is_renewal_tier_email_sent' => $lead->is_renewal_tier_email_sent,
                'lead_allocation_failed_at' => $lead->lead_allocation_failed_at,
                'sic_flow_enabled' => $lead->sic_flow_enabled,
                'sic_advisor_requested' => $lead->sic_advisor_requested,
                'quote_status_id' => $lead->quote_status_id,
            ]);

            // Only apply teamId if the payment status is AUTHORIZED
            $currentTeamId = $lead->payment_status_id == PaymentStatusEnum::AUTHORISED ? $teamId : false;

            QuoteTypes::CAR->allocate(uuid: $lead->uuid, teamId: $currentTeamId);
            $processedRecords++;
            info('Processed record for Quote Allocation with uuid: '.$lead->uuid);
        }

        $this->logProcessedRecords($processedRecords, $quoteType);
    }

    public function executeHealthAllocation($quoteType, $to, $chunkSize, $allocationStartDate)
    {
        $processedRecords = 0;
        $leads = HealthQuote::whereNull('advisor_id')
            ->select('uuid', 'payment_status_id', 'sic_advisor_requested', 'quote_status_id', 'lead_allocation_failed_at', 'sic_flow_enabled')
            ->whereBetween('created_at', [$allocationStartDate, $to])
            ->orderBy('created_at', 'desc')
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate, QuoteStatusEnum::Lost])
            ->where('health_quote_request.price_starting_from', '!=', null)
            ->where('health_quote_request.is_error_email_sent', 0)
            ->where('source', '!=', LeadSourceEnum::IMCRM)
            ->where(function ($q) {
                $q->leadAllocationFailed()
                    ->orWhere->sicFlowDisabled()
                    ->orWhere(function ($subQuery) {
                        $subQuery->sicFlowEnabled()->requestedAdvisorOrPaymentAuthorized();
                    });
            })
            ->take($chunkSize);

        info("For Health - leads fetch query is : {$leads->toRawSql()}");

        foreach ($leads->get() as $lead) {
            info("Processing Health record for Quote Allocation with uuid: {$lead->uuid}", [
                'uuid' => $lead->uuid,
                'payment_status_id' => $lead->payment_status_id,
                'sic_advisor_requested' => $lead->sic_advisor_requested,
                'quote_status_id' => $lead->quote_status_id,
                'lead_allocation_failed_at' => $lead->lead_allocation_failed_at,
                'sic_flow_enabled' => $lead->sic_flow_enabled,
                'price_starting_from' => $lead->price_starting_from,
            ]);
            QuoteTypes::HEALTH->allocate(uuid: $lead->uuid);
            $processedRecords++;
            info('Processed Health record for Quote Allocation with uuid: '.$lead->uuid);
        }

        $this->logProcessedRecords($processedRecords, $quoteType);
    }

    public function executeTravelAllocation($quoteType, $to, $chunkSize, $allocationStartDate)
    {
        $processedRecords = 0;
        $leads = TravelQuote::with('parent')
            ->whereNull('advisor_id')
            ->select('uuid', 'payment_status_id', 'sic_advisor_requested', 'quote_status_id', 'lead_allocation_failed_at', 'sic_flow_enabled')
            ->whereBetween('created_at', [$allocationStartDate, $to])
            ->orderBy('created_at', 'desc')
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate, QuoteStatusEnum::Lost])
            ->where(function ($q) {
                $q->leadAllocationFailed()
                    ->orWhere->sicFlowDisabled()
                    ->orWhere(function ($subQuery) {
                        $subQuery->sicFlowEnabled()->requestedAdvisorOrPaymentAuthorized();
                    });
            })
            ->take($chunkSize);

        info("For Travel - leads fetch query is : {$leads->toRawSql()}");

        // Get the teamId once before the loop
        $teamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);

        foreach ($leads->get() as $lead) {
            // Skip the child leads if the parent lead does not have an advisor
            if ($lead->isChild() && empty($lead->parent?->advisor_id)) {
                info('Skipping Travel record for Quote Allocation with uuid: '.$lead->uuid.' as parent lead does not have an advisor');

                continue;
            }

            info("Processing Travel record for Quote Allocation with uuid: {$lead->uuid}", [
                'uuid' => $lead->uuid,
                'payment_status_id' => $lead->payment_status_id,
                'sic_advisor_requested' => $lead->sic_advisor_requested,
                'quote_status_id' => $lead->quote_status_id,
                'lead_allocation_failed_at' => $lead->lead_allocation_failed_at,
                'sic_flow_enabled' => $lead->sic_flow_enabled,
                'parent_quote_id' => $lead->parent_id,
            ]);

            // Only apply teamId if the payment status is AUTHORIZED
            $currentTeamId = $lead->payment_status_id == PaymentStatusEnum::AUTHORISED ? $teamId : false;

            QuoteTypes::TRAVEL->allocate(uuid: $lead->uuid, teamId: $currentTeamId);
            $processedRecords++;
            info('Processed Travel record for Quote Allocation with uuid: '.$lead->uuid);
        }

        $this->logProcessedRecords($processedRecords, $quoteType);
    }

    private function logProcessedRecords($processedRecords, mixed $quoteType)
    {
        if ($processedRecords === 0) {
            info('No records found for '.($quoteType instanceof QuoteTypes ? $quoteType->value : QuoteTypeId::getDescription($quoteType)));
        }
    }

    public function executeBikeAllocation($quoteType, $to, $chunkSize, $allocationStartDate, $applicationStorageService)
    {
        $processedRecords = 0;
        $shouldIncludeDubaiNow = $applicationStorageService->getValueByKey(ApplicationStorageEnums::APPLY_DUBAI_NOW_EXCLUSION) == 1;
        $exemptedLeadSources = [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD];

        if ($shouldIncludeDubaiNow) {
            $exemptedLeadSources[] = LeadSourceEnum::DUBAI_NOW;
        }

        $leads = PersonalQuote::whereNull('advisor_id')
            ->select('uuid')
            ->whereBetween('created_at', [$allocationStartDate, $to])
            ->orderBy('created_at', 'desc')
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('source', $exemptedLeadSources)
            ->where('quote_type_id', QuoteTypeId::Bike)
            ->take($chunkSize);

        info('For Bike - leads fetch query is : '.$leads->toSql().' with params : '.json_encode($leads->getBindings()));

        foreach ($leads->get() as $lead) {
            if ($lead->tier_id == TiersIdEnum::TIER_R) {
                continue;
            }
            info('Processing record for Bike Quote Allocation with uuid: '.$lead->uuid);
            QuoteTypes::BIKE->allocate(uuid: $lead->uuid);
            $processedRecords++;
            info('Processed record for Bike Quote Allocation with uuid: '.$lead->uuid);
        }
        $this->logProcessedRecords($processedRecords, $quoteType);
    }

    private function executeAllocation(QuoteTypes $quoteType, $to, $chunkSize, $allocationStartDate)
    {
        $processedRecords = 0;
        $leads = $quoteType->model()::whereNull('advisor_id')
            ->select('uuid', 'payment_status_id')
            ->whereBetween('created_at', [$allocationStartDate, $to])
            ->orderBy('created_at', 'desc')
            ->when($quoteType->isPersonalQuote(), function ($q) use ($quoteType) {
                $q->where('quote_type_id', $quoteType->id());
            })
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate, QuoteStatusEnum::Lost])
            ->take($chunkSize);

        foreach ($leads->get() as $lead) {
            info("Processing record for Quote Allocation with uuid: {$lead->uuid} and Quote Type: {$quoteType->value}");
            $quoteType->allocate(uuid: $lead->uuid);
            $processedRecords++;
            info("Processed record for Quote Allocation with uuid: {$lead->uuid} and Quote Type: {$quoteType->value}");
        }
        $this->logProcessedRecords($processedRecords, $quoteType);
    }
}
