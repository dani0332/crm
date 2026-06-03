<?php

namespace App\Services\Allocation;

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
use App\Services\BuyLeads\BuyLeadService;
use App\Services\BuyLeads\CatARevivalAllocationPriorityService;
use App\Services\Logger\LoggerService;

class RetryAllocationService
{
    public function verifyRetryAllocationMasterSwitch(QuoteTypes $quoteType): ?array
    {
        $currentIteration = now();
        LoggerService::info(self::class.': Quote Allocation Command Started', extra: [
            'quote_type' => $quoteType->value,
            'timestamp' => $currentIteration,
        ]);

        $quoteAllocationSwitch = getAppStorageValueByKey(ApplicationStorageEnums::QUOTE_ALLOCATION_SWITCH, useCache: true);

        $masterSwitchConfigValue = (int) config('constants.QUOTE_ALLOCATION_MASTER_SWITCH');
        $startTime = now()->subWeek()->startOfDay()->toDateTimeString();
        if ($quoteAllocationSwitch == 1 && $masterSwitchConfigValue == 1) {
            $endTime = now()->subMinutes(5)->toDateTimeString();

            return [$startTime, $endTime];
        }

        LoggerService::warning(self::class.": Retry Allocation Command is turned Off for quote type {$quoteType->value}");

        return null;
    }

    public function executeCarAllocation($quoteType, $to, $chunkSize, $allocationStartDate)
    {
        $processedRecords = 0;
        $shouldIncludeDubaiNow = getAppStorageValueByKey(ApplicationStorageEnums::APPLY_DUBAI_NOW_EXCLUSION, useCache: true) == 1;
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
                'code',
                'payment_status_id',
                'source',
                'is_renewal_tier_email_sent',
                'lead_allocation_failed_at',
                'sic_flow_enabled',
                'sic_advisor_requested',
                'quote_status_id',
                'tier_id',
                'car_value',
            ])
            ->where(function ($q) use ($allocationStartDate, $to) {
                $q->whereBetween('created_at', [$allocationStartDate, $to])
                    ->orWhere(function ($sq) use ($to) {
                        $sq->advisorRequestedOrPaymentAuthorizedOrDeclined()
                            ->whereBetween('created_at', [now()->subDays(60), $to]);
                    });
            })
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->where(function ($q) use ($exemptedLeadSources) {
                $q->whereNotIn('source', $exemptedLeadSources)
                    ->orWhere(fn ($revival) => $revival->whereRevivalIntentRetryEligible());
            })
            ->orderByDesc('created_at')
            ->where(function ($q) {
                $q->eligibleForAllocation(QuoteTypes::CAR);
                $q->orWhere(function ($sq) {
                    $sq->whereNull('advisor_id')->where('ai_advisor_required', true);
                });
                $q->orWhere(fn ($sq) => $sq->whereRevivalIntentRetryEligible());
                $q->orWhere(fn ($sq) => $sq->whereRevivalReinstatedRetryEligible());
            })
            ->take($chunkSize);

        $leads->logRawSql();

        // Get the teamId once before the loop
        $teamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);

        $leads = $leads->get();
        LoggerService::info(self::class.':executeCarAllocation: Found '.count($leads).' leads to process');

        foreach ($leads as $lead) {
            if ($lead->tier_id == TiersIdEnum::TIER_R && ! $lead->hasCarValue()) {
                LoggerService::info(self::class.': Skipping car quote allocation for tier R and does not have car value');

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
            $revivalSources = [LeadSourceEnum::REVIVAL_REPLIED, LeadSourceEnum::REVIVAL_PAID];
            $currentTeamId = false;
            if ($lead->payment_status_id == PaymentStatusEnum::AUTHORISED && ! in_array($lead->source, $revivalSources)) {
                $currentTeamId = $teamId;
            }

            QuoteTypes::CAR->allocate(uuid: $lead->uuid, teamId: $currentTeamId);
            $processedRecords++;
            LoggerService::info(self::class.': Processed car quote allocation');
        }

        $this->logProcessedRecords($processedRecords, $quoteType);
    }

    public function executeCarRevivalAllocation($quoteType, $to, $chunkSize, $allocationStartDate)
    {
        $processedRecords = 0;
        LoggerService::info(self::class.': Executing car revival quote allocation for cat A nationalities');
        $nationalityIds = BuyLeadService::getNationalitiesIds(QuoteTypes::CAR_CAT_A);

        if ($nationalityIds === []) {
            LoggerService::warning(self::class.'::executeCarRevivalAllocation - No CAT A nationalities configured; skipping');

            return;
        }

        $carValueOrderExpr = CatARevivalAllocationPriorityService::effectiveCarValueExpressionSql('car_quote_request');

        $leads = CarQuote::query()
            ->whereIn('nationality_id', $nationalityIds)
            ->where('source', LeadSourceEnum::REVIVAL)
            ->whereNull('advisor_id')
            ->select([
                'id',
                'uuid',
                'payment_status_id',
                'source',
                'is_renewal_tier_email_sent',
                'lead_allocation_failed_at',
                'sic_flow_enabled',
                'sic_advisor_requested',
                'quote_status_id',
                'advisor_id',
                'tier_id',
                'car_value',
                'car_value_tier',
                'created_at',
            ])
            ->whereBetween('created_at', [$allocationStartDate, $to])
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->orderByRaw('('.$carValueOrderExpr.') DESC')
            ->orderBy('created_at', 'asc')
            ->take($chunkSize);

        $leads->logRawSql();

        $teamId = getTeamId(TeamNameEnum::ORGANIC);

        $leads = $leads->get();
        LoggerService::info(self::class.':executeCarRevivalAllocation: Found '.count($leads).' CAT A Revival leads to process');

        if ($leads->isNotEmpty()) {
            LoggerService::info(self::class.':executeCarRevivalAllocation: Priority queue (highest car value first)', extra: [
                'queue' => $leads->map(fn ($l, $index) => [
                    'priority' => $index + 1,
                    'uuid' => $l->uuid,
                    'effective_car_value' => CatARevivalAllocationPriorityService::effectiveCarValue($l),
                    'car_value' => $l->car_value,
                    'created_at' => $l->created_at,
                ])->values()->all(),
            ]);
        }

        foreach ($leads as $index => $lead) {
            LoggerService::startQuoteLogging($lead, LoggerFeatureEnum::ALLOCATION);

            if ($lead->tier_id == TiersIdEnum::TIER_R && ! $lead->hasCarValue()) {
                LoggerService::info(self::class.': Skipping car revival quote allocation for tier R and does not have car value');

                continue;
            }

            LoggerService::info(self::class.': Processing CAT A Revival car quote allocation', extra: [
                'priority_position' => $index + 1,
                'total_in_queue' => $leads->count(),
                'uuid' => $lead->uuid,
                'effective_car_value' => CatARevivalAllocationPriorityService::effectiveCarValue($lead),
                'car_value' => $lead->car_value,
                'car_value_tier' => $lead->car_value_tier,
                'payment_status_id' => $lead->payment_status_id,
                'source' => $lead->source,
                'is_renewal_tier_email_sent' => $lead->is_renewal_tier_email_sent,
                'lead_allocation_failed_at' => $lead->lead_allocation_failed_at,
                'sic_flow_enabled' => $lead->sic_flow_enabled,
                'sic_advisor_requested' => $lead->sic_advisor_requested,
                'quote_status_id' => $lead->quote_status_id,
            ]);

            // All revival sources should be assigned to ORGANIC team
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

        $leads = $leads->get();
        LoggerService::info(self::class.':executeHealthAllocation: Found '.count($leads).' leads to process');

        foreach ($leads as $lead) {
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

        $leads = $leads->get();
        LoggerService::info(self::class.':executeTravelAllocation: Found '.count($leads).' leads to process');

        foreach ($leads as $lead) {
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

    public function executeBikeAllocation($quoteType, $to, $chunkSize, $allocationStartDate)
    {
        $processedRecords = 0;
        $shouldIncludeDubaiNow = getAppStorageValueByKey(ApplicationStorageEnums::APPLY_DUBAI_NOW_EXCLUSION, useCache: true) == 1;
        $exemptedLeadSources = [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD];

        if ($shouldIncludeDubaiNow) {
            $exemptedLeadSources[] = LeadSourceEnum::DUBAI_NOW;
        }

        $leads = PersonalQuote::whereNull('advisor_id')
            ->select('uuid', 'tier_id', 'payment_status_id', 'quote_status_id', 'lead_allocation_failed_at', 'source')
            ->whereBetween('created_at', [$allocationStartDate, $to])
            ->orderBy('created_at', 'desc')
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('source', $exemptedLeadSources)
            ->where('quote_type_id', QuoteTypeId::Bike)
            ->take($chunkSize);

        $leads->logRawSql();

        $leads = $leads->get();
        LoggerService::info(self::class.':executeBikeAllocation: Found '.count($leads).' leads to process');

        foreach ($leads as $lead) {
            if ($lead->tier_id == TiersIdEnum::TIER_R) {
                continue;
            }

            LoggerService::startQuoteLogging($lead, LoggerFeatureEnum::ALLOCATION);
            LoggerService::info(self::class.': Processing bike quote allocation', extra: [
                'payment_status_id' => $lead->payment_status_id,
                'quote_status_id' => $lead->quote_status_id,
                'lead_allocation_failed_at' => $lead->lead_allocation_failed_at,
                'source' => $lead->source,
                'tier_id' => $lead->tier_id,
            ]);

            QuoteTypes::BIKE->allocate(uuid: $lead->uuid);
            $processedRecords++;
            LoggerService::info(self::class.': Processed bike quote allocation');
        }
        $this->logProcessedRecords($processedRecords, $quoteType);
    }

    public function executeAllocation(QuoteTypes $quoteType, $to, $chunkSize, $allocationStartDate)
    {
        $processedRecords = 0;
        $leads = $quoteType->model()::whereNull('advisor_id')
            ->select('uuid', 'payment_status_id', 'quote_status_id', 'lead_allocation_failed_at', 'source')
            ->orderBy('created_at', 'desc')
            ->when($quoteType->isPersonalQuote(), function ($q) use ($quoteType) {
                $q->where('quote_type_id', $quoteType->id());
            })
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate, QuoteStatusEnum::Lost])
            ->when($quoteType === QuoteTypes::GROUP_MEDICAL, function ($q) {
                $q->where('business_type_of_insurance_id', BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL)
                    ->whereNotNull('health_plan_type_id')
                    ->whereNotNull('number_of_employees');
            })
            ->when($quoteType === QuoteTypes::CORPLINE, function ($q) {
                $q->where('business_type_of_insurance_id', '!=', BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL);
            })
            ->when($quoteType === QuoteTypes::CYBER, function ($q) use ($allocationStartDate, $to) {
                $q->forRetryAllocationCyber($allocationStartDate, $to);
            }, function ($q) use ($allocationStartDate, $to) {
                $q->whereBetween('created_at', [$allocationStartDate, $to]);
            })
            ->when($quoteType === QuoteTypes::DEVICE, function ($q) {
                $q->with('deviceQuote:id,personal_quote_id,sic_advisor_requested');
            })
            ->when($quoteType === QuoteTypes::LIFE, function ($q) {
                $q->where(function ($lifeQuery) {
                    $lifeQuery->where('source', '!=', LeadSourceEnum::REVIVAL)
                        ->orWhereNull('source');
                });
            })
            ->when(in_array($quoteType, [QuoteTypes::HOME, QuoteTypes::HOME_REVIVAL]), function ($q) {
                $q->where(function ($lifeQuery) {
                    $lifeQuery->whereNotIn('source', [LeadSourceEnum::REVIVAL_SHORT, LeadSourceEnum::REVIVAL_ANNUAL])
                        ->orWhereNull('source');
                });
            })
            ->take($chunkSize);

        $leads->logRawSql();

        $leads = $leads->get();
        LoggerService::info(self::class.':executeAllocation: Found '.count($leads).' leads to process');

        foreach ($leads as $lead) {
            LoggerService::startQuoteLogging($lead, LoggerFeatureEnum::ALLOCATION);

            $isPaid = $lead->isPaymentAuthorizedOrDeclined();
            $sicRequested = $quoteType === QuoteTypes::CYBER ? $lead->cyberQuote?->sic_advisor_requested ?? false : false;

            LoggerService::info(self::class.': Processing quote allocation', extra: [
                'quote_type' => $quoteType->value,
                'payment_status_id' => $lead->payment_status_id,
                'quote_status_id' => $lead->quote_status_id,
                'lead_allocation_failed_at' => $lead->lead_allocation_failed_at,
                'source' => $lead->source,
                'isPaid' => $isPaid,
                'sicAdvisorRequested' => $sicRequested,
            ]);

            $quoteType->allocate(uuid: $lead->uuid);
            $processedRecords++;

            LoggerService::info(self::class.': Processed quote allocation', extra: [
                'quote_type' => $quoteType->value,
            ]);
        }

        $this->logProcessedRecords($processedRecords, $quoteType);
    }
}
