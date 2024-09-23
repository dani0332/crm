<?php

namespace App\Services\Reports;

use App\Enums\GenericRequestEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Services\ApplicationStorageService;
use Carbon\Carbon;

trait Reportable
{
    public function getStartAndEndDate($filters)
    {
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');
        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);
        $freshLoad = ! isset($filters->page);

        if (isset($filters->advisorAssignedDates)) {
            $startDate = Carbon::parse($filters->advisorAssignedDates[0])->startOfDay()->format($dateFormat);
        } else {
            $startDate = $freshLoad
                ? now()->startOfDay()->format($dateFormat)
                : now()->subDays((int) $maxDays)->startOfDay()->format($dateFormat);
        }

        $endDate = isset($filters->advisorAssignedDates)
            ? Carbon::parse($filters->advisorAssignedDates[1])->endOfDay()->format($dateFormat)
            : now()->endOfDay()->format($dateFormat);

        return [$freshLoad, $startDate, $endDate];
    }

    public function getExcludedSources()
    {
        return [LeadSourceEnum::IMCRM, LeadSourceEnum::INSLY];
    }

    public function getNotInterestedStatuses()
    {
        return [
            QuoteStatusEnum::PriceTooHigh,
            QuoteStatusEnum::PolicyPurchasedBeforeFirstCall,
            QuoteStatusEnum::NotInterested,
            QuoteStatusEnum::NotEligibleForInsurance,
            QuoteStatusEnum::NotLookingForMotorInsurance,
            QuoteStatusEnum::NonGccSpec,
            QuoteStatusEnum::AMLScreeningFailed,
        ];
    }

    public function getInProgressStatuses()
    {
        return [
            QuoteStatusEnum::NotContactablePe,
            QuoteStatusEnum::FollowupCall,
            QuoteStatusEnum::Interested,
            QuoteStatusEnum::NoAnswer,
            QuoteStatusEnum::Quoted,
            QuoteStatusEnum::PaymentPending,
            QuoteStatusEnum::AMLScreeningCleared,
            QuoteStatusEnum::PendingQuote,
        ];
    }

    public function getBadLeadStatuses()
    {
        return [
            QuoteStatusEnum::Duplicate,
            QuoteStatusEnum::Fake,
        ];
    }

    public function getPaidStatuses()
    {
        return [
            PaymentStatusEnum::CAPTURED,
        ];
    }
}
