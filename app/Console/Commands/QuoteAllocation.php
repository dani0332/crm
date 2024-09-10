<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\TiersIdEnum;
use App\Factories\AllocationFactory;
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
            ->select('uuid')
            ->whereBetween('created_at', [$allocationStartDate, $to])
            ->orderBy('created_at', 'desc')
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('source', $exemptedLeadSources)
            ->where('is_renewal_tier_email_sent', 0)
            ->where(function ($query) {
                $query->where('source', '!=', LeadSourceEnum::RENEWAL_UPLOAD)->where('sic_flow_enabled', 0)
                    ->orWhere(function ($query) {
                        $query->where('source', LeadSourceEnum::RENEWAL_UPLOAD)->where('sic_advisor_requested', 1)->where('sic_flow_enabled', 1);
                    });
            })
            ->take($chunkSize);

        info('leads fetch query is : '.$leads->toSql().' with params : '.json_encode($leads->getBindings()));

        foreach ($leads->get() as $lead) {
            if ($lead->tier_id == TiersIdEnum::TIER_R) {
                continue;
            }
            info('Processing record for Quote Allocation with uuid: '.$lead->uuid);
            $allocationStrategy = AllocationFactory::createStrategy($quoteType, $lead->uuid);
            $allocationStrategy->executeSteps();
            $processedRecords++;
            info('Processed record for Quote Allocation with uuid: '.$lead->uuid);
        }

        $this->logProcessedRecords($processedRecords, $quoteType);
    }

    public function executeHealthAllocation($quoteType, $to, $chunkSize, $allocationStartDate)
    {
        $processedRecords = 0;
        $leads = HealthQuote::whereNull('advisor_id')
            ->select('uuid')
            ->whereBetween('created_at', [$allocationStartDate, $to])
            ->orderBy('created_at', 'desc')
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate, QuoteStatusEnum::Lost])
            ->where('health_quote_request.price_starting_from', '!=', null)
            ->where('health_quote_request.is_error_email_sent', 0)
            ->where('health_quote_request.advisor_id', null)
            ->where('sic_flow_enabled', 0)
            ->orWhere(function ($query) {
                $query->where('sic_advisor_requested', 1)->where('sic_flow_enabled', 1);
            })->take($chunkSize);
        info('Health leads fetch query is : '. $leads->toRawSql());
        foreach ($leads->get() as $lead) {
            $allocationStrategy = AllocationFactory::createStrategy($quoteType, $lead->uuid);
            $allocationStrategy->executeSteps();
            $processedRecords++;
        }

        $this->logProcessedRecords($processedRecords, $quoteType);
    }

    public function executeTravelAllocation($quoteType, $to, $chunkSize, $allocationStartDate)
    {
        $processedRecords = 0;
        $leads = TravelQuote::whereNull('advisor_id')
            ->select('uuid')
            ->whereBetween('created_at', [$allocationStartDate, $to])
            ->orderBy('created_at', 'desc')
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate, QuoteStatusEnum::Lost])
            ->where('sic_flow_enabled', false)
            ->take($chunkSize);

        foreach ($leads->get() as $lead) {
            $allocationStrategy = AllocationFactory::createStrategy($quoteType, $lead->uuid);
            $allocationStrategy->executeSteps();
            $processedRecords++;
        }

        $this->logProcessedRecords($processedRecords, $quoteType);
    }

    private function logProcessedRecords($processedRecords, $quoteType)
    {
        if ($processedRecords === 0) {
            info('No records found for '.QuoteTypeId::getDescription($quoteType));
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
            $allocationStrategy = AllocationFactory::createStrategy($quoteType, $lead->uuid);
            $allocationStrategy->executeSteps();
            $processedRecords++;
            info('Processed record for Bike Quote Allocation with uuid: '.$lead->uuid);
        }
        $this->logProcessedRecords($processedRecords, $quoteType);
    }
}
