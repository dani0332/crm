<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\Logger\LoggerFeatureEnum;
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
use App\Models\User;
use App\Services\ApplicationStorageService;
use App\Services\BuyLeads\BuyLeadService;
use App\Services\Logger\LoggerService;
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
        LoggerService::info(self::class.': Quote Allocation Command Started', extra: [
            'timestamp' => $currentIteration,
        ]);

        $quoteAllocationSwitch = $applicationStorageService->getValueByKey(ApplicationStorageEnums::QUOTE_ALLOCATION_SWITCH);
        $masterSwitchConfigValue = (int) config('constants.QUOTE_ALLOCATION_MASTER_SWITCH');
        $allocationStartDate = now()->subWeek()->startOfDay()->toDateTimeString();
        if ($quoteAllocationSwitch == 1 && $masterSwitchConfigValue == 1) {
            $to = now()->subMinutes(5)->toDateTimeString();
            $chunkSize = 200;
            LoggerService::info(self::class.': Setting allocation date range', extra: [
                'start_date' => $allocationStartDate,
                'end_date' => $to,
            ]);
            $this->executeCarAllocation(QuoteTypeId::Car, $to, $chunkSize, $allocationStartDate, $applicationStorageService);
            $this->executeHealthAllocation(QuoteTypeId::Health, $to, $chunkSize, $allocationStartDate);
            $this->executeBikeAllocation(QuoteTypeId::Bike, $to, $chunkSize, $allocationStartDate, $applicationStorageService);
            $this->executeTravelAllocation(QuoteTypeId::Travel, $to, $chunkSize, $allocationStartDate);
            $this->executeAllocation(QuoteTypes::GROUP_MEDICAL, $to, $chunkSize, $allocationStartDate);

            $this->executeAllocation(QuoteTypes::HOME, $to, $chunkSize, $allocationStartDate);
            $this->executeAllocation(QuoteTypes::LIFE, $to, $chunkSize, $allocationStartDate);
            $this->executeAllocation(QuoteTypes::CORPLINE, $to, $chunkSize, $allocationStartDate);

            $this->executeAllocation(QuoteTypes::CYCLE, $to, $chunkSize, $allocationStartDate);
            $this->executeAllocation(QuoteTypes::PET, $to, $chunkSize, $allocationStartDate);
            $this->executeAllocation(QuoteTypes::YACHT, $to, $chunkSize, $allocationStartDate);
            $this->executeAllocation(QuoteTypes::SAVINGS, $to, $chunkSize, $allocationStartDate);
            $this->executeCarRevivalAllocation(QuoteTypeId::Car, $to, $chunkSize, $allocationStartDate, $applicationStorageService);
            LoggerService::endLogging();
        } else {
            LoggerService::info(self::class.': Quote Allocation Command is turned Off');
        }

        LoggerService::info(self::class.': Quote Allocation Command Finished', extra: [
            'timestamp' => $currentIteration,
        ]);
    }

    public function executeCarAllocation($quoteType, $to, $chunkSize, $allocationStartDate, $applicationStorageService)
    {
        $processedRecords = 0;
        $shouldIncludeDubaiNow = $applicationStorageService->getValueByKey(ApplicationStorageEnums::APPLY_DUBAI_NOW_EXCLUSION) == 1;
        $exemptedLeadSources = [LeadSourceEnum::IMCRM, LeadSourceEnum::INSLY, LeadSourceEnum::REVIVAL];

        if ($shouldIncludeDubaiNow) {
            $exemptedLeadSources[] = LeadSourceEnum::DUBAI_NOW;
        }

        $leads = CarQuote::query()
            ->where(function ($q) {
                $q->whereNull('advisor_id');
                $q->orWhere(function ($sq) {
                    $sq->where('advisor_id', User::getAiAdvisor()->id);
                    $sq->where('ai_advisor_required', false);
                });
            })
            ->select([
                'uuid',
                'payment_status_id',
                'source',
                'is_renewal_tier_email_sent',
                'lead_allocation_failed_at',
                'sic_flow_enabled',
                'sic_advisor_requested',
                'quote_status_id',
            ])
            ->where('created_at', '<=', $to)
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('source', $exemptedLeadSources)
            ->orderByDesc('created_at')
            ->where(function ($q) {
                $q->eligibleForAllocation(QuoteTypes::CAR);
                $q->orWhere(function ($sq) {
                    $sq->whereNull('advisor_id')->where('ai_advisor_required', true);
                });
            })
            ->take($chunkSize);

        $leads->logRawSql();

        // Get the teamId once before the loop
        $teamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);

        foreach ($leads->get() as $lead) {
            if ($lead->tier_id == TiersIdEnum::TIER_R) {
                continue;
            }

            LoggerService::startQuoteLogging($lead, LoggerFeatureEnum::ALLOCATION);

            LoggerService::info(self::class.': Processing car quote allocation', extra: [
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
            LoggerService::info(self::class.': Processed car quote allocation');
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
                    ->orWhere->hasPecTag()
                    ->orWhere(function ($subQuery) {
                        $subQuery->sicFlowEnabled()->advisorRequestedOrPaymentAuthorizedOrDeclined();
                    });
            })
            ->take($chunkSize);

        $leads->logRawSql();

        foreach ($leads->get() as $lead) {
            LoggerService::startQuoteLogging($lead, LoggerFeatureEnum::ALLOCATION);

            LoggerService::info(self::class.': Processing health quote allocation', extra: [
                'payment_status_id' => $lead->payment_status_id,
                'sic_advisor_requested' => $lead->sic_advisor_requested,
                'quote_status_id' => $lead->quote_status_id,
                'lead_allocation_failed_at' => $lead->lead_allocation_failed_at,
                'sic_flow_enabled' => $lead->sic_flow_enabled,
                'price_starting_from' => $lead->price_starting_from,
            ]);
            QuoteTypes::HEALTH->allocate(uuid: $lead->uuid);
            $processedRecords++;
            LoggerService::info(self::class.': Processed health quote allocation');
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
            ->eligibleForAllocation(QuoteTypes::TRAVEL)
            ->take($chunkSize);

        $leads->logRawSql();

        // Get the teamId once before the loop
        $teamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);

        foreach ($leads->get() as $lead) {
            // Skip the child leads if the parent lead does not have an advisor
            if ($lead->isChild() && empty($lead->parent?->advisor_id)) {
                LoggerService::info(self::class.': Skipping travel quote allocation', extra: [
                    'uuid' => $lead->uuid,
                    'reason' => 'parent lead does not have an advisor',
                ]);

                continue;
            }

            LoggerService::startQuoteLogging($lead, LoggerFeatureEnum::ALLOCATION);

            LoggerService::info(self::class.': Processing travel quote allocation', extra: [
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
            LoggerService::info(self::class.': Processed travel quote allocation');
        }

        $this->logProcessedRecords($processedRecords, $quoteType);
    }

    private function logProcessedRecords($processedRecords, mixed $quoteType)
    {
        if ($processedRecords === 0) {
            LoggerService::info(self::class.': No records found', extra: [
                'quote_type' => $quoteType instanceof QuoteTypes ? $quoteType->value : QuoteTypeId::getDescription($quoteType),
            ]);
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

        $leads->logRawSql();

        foreach ($leads->get() as $lead) {
            if ($lead->tier_id == TiersIdEnum::TIER_R) {
                continue;
            }

            LoggerService::startQuoteLogging($lead, LoggerFeatureEnum::ALLOCATION);

            LoggerService::info(self::class.': Processing bike quote allocation');
            QuoteTypes::BIKE->allocate(uuid: $lead->uuid);
            $processedRecords++;
            LoggerService::info(self::class.': Processed bike quote allocation');
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
            ->when($quoteType === QuoteTypes::GROUP_MEDICAL, function ($q) {
                $q->where('business_type_of_insurance_id', BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL);
            })
            ->when($quoteType === QuoteTypes::CORPLINE, function ($q) {
                $q->where('business_type_of_insurance_id', '!=', BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL);
            })
            ->take($chunkSize);

        $leads->logRawSql();

        foreach ($leads->get() as $lead) {
            LoggerService::startQuoteLogging($lead, LoggerFeatureEnum::ALLOCATION);

            LoggerService::info(self::class.': Processing quote allocation', extra: [
                'quote_type' => $quoteType->value,
            ]);
            $quoteType->allocate(uuid: $lead->uuid);
            $processedRecords++;
            LoggerService::info(self::class.': Processed quote allocation', extra: [
                'quote_type' => $quoteType->value,
            ]);
        }

        $this->logProcessedRecords($processedRecords, $quoteType);
    }

    public function executeCarRevivalAllocation($quoteType, $to, $chunkSize, $allocationStartDate, $applicationStorageService)
    {
        $processedRecords = 0;
        LoggerService::info(self::class.': Executing car revival quote allocation for cat A nationalities');
        $nationalityIds = app(BuyLeadService::class)->getCarCatANationalitiesIds();

        $leads = CarQuote::query()
            ->whereIn('nationality_id', $nationalityIds)
            ->where('source', LeadSourceEnum::REVIVAL)
            ->where(function ($q) {
                $q->whereNull('advisor_id');
                $q->orWhere(function ($sq) {
                    $sq->where('advisor_id', User::getAiAdvisor()->id);
                    $sq->where('ai_advisor_required', false);
                });
            })
            ->select([
                'uuid',
                'payment_status_id',
                'source',
                'is_renewal_tier_email_sent',
                'lead_allocation_failed_at',
                'sic_flow_enabled',
                'sic_advisor_requested',
                'quote_status_id',
            ])
            ->where('created_at', '<=', $to)
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->orderByDesc('created_at')
            ->where(function ($q) {
                $q->eligibleForAllocation(QuoteTypes::CAR);
                $q->orWhere(function ($sq) {
                    $sq->whereNull('advisor_id')->where('ai_advisor_required', true);
                });
            })
            ->take($chunkSize);

        $leads->logRawSql();

        // Get the teamId once before the loop
        $teamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);

        foreach ($leads->get() as $lead) {
            if ($lead->tier_id == TiersIdEnum::TIER_R) {
                continue;
            }

            LoggerService::startQuoteLogging($lead, LoggerFeatureEnum::ALLOCATION);

            LoggerService::info(self::class.': Processing car quote allocation', extra: [
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
            LoggerService::info(self::class.': Processed car quote allocation');
        }

        $this->logProcessedRecords($processedRecords, $quoteType);
    }
}
