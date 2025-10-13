<?php

declare(strict_types=1);

namespace App\Services\SLA;

use App\Enums\EnvEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\SLAStatusEnum;
use App\Enums\TeamNameEnum;
use App\Models\HealthQuote;
use App\Models\SLATracking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

trait SLAable
{
    private function isLOBEnabled(Model $lead): bool
    {
        return $lead && $lead instanceof HealthQuote;
    }

    private static function getMeetableQuoteStatuses(): array
    {
        return [
            QuoteStatusEnum::FollowedUp,
            QuoteStatusEnum::InNegotiation,
            QuoteStatusEnum::PaymentLinkInprogress,
            QuoteStatusEnum::PaymentLinkSentToCustomer,
            QuoteStatusEnum::PaymentInitiated,
            QuoteStatusEnum::MissingDocumentsRequested,
            QuoteStatusEnum::PolicyDocumentsPending,
            QuoteStatusEnum::SentForTransactionApproval,
            QuoteStatusEnum::KYCCleared,
            QuoteStatusEnum::AMLScreeningCleared,
            QuoteStatusEnum::AMLScreeningFailed,
            QuoteStatusEnum::Lost,
            QuoteStatusEnum::Fake,
        ];
    }

    private function getActiveSLA(Model $lead): ?SLATracking
    {
        return SLATracking::byLead($lead)->active()->latest('id')->first();
    }

    private function hasAnyFinalSLA(Model $lead): bool
    {
        return SLATracking::byLead($lead)->whereIn('status', SLAStatusEnum::getFinalStatuses())->exists();
    }

    private function getAssignmentTime(QuoteTypes $quoteType, Model $lead): Carbon
    {
        return Carbon::parse($quoteType->detailModel()->where($lead->getForeignKey(), $lead->id)->value('advisor_assigned_date') ?? now());
    }

    private function isAssignmentTimeWithinBusinessHours(Carbon $assignmentTime): bool
    {
        $businessStart = $this->allocationService->getBusinessStartTime();
        $businessEnd = $this->allocationService->getBusinessEndTime();

        $businessStartTime = $assignmentTime->copy()->setTimeFromTimeString($businessStart);
        $businessEndTime = $assignmentTime->copy()->setTimeFromTimeString($businessEnd);

        return $assignmentTime->isBetween($businessStartTime, $businessEndTime);
    }

    private function getHeadEmail(): string
    {
        if ($this->isProduction()) {
            return 'agatha.alicdan@insurancemarket.ae';
        }

        return 'usman.iqbal@myalfred.com';
    }

    private function getCCEmails(User $advisor): array
    {
        $managerEmails = $this->getManagersEmails($advisor);

        return [
            $advisor->email,
            ...$managerEmails,
        ];
    }

    private function getManagersEmails(User $advisor)
    {
        if (! $this->isProduction()) {
            return [
                'usman.iqbal@myalfred.com'
            ];
        }

        $advisorTeams = $advisor->teams->filter(function ($team) {
            return $team->is_active && $team->type === 'Team';
        })->pluck('name')->toArray();

        $managerEmails = [];

        if (in_array(TeamNameEnum::PCP, $advisorTeams)) {
            $managerEmails = [
                ...$managerEmails,
                'moinuddin.lakdawala@insurancemarket.ae',
            ];
        }

        if (in_array(TeamNameEnum::RM_SPEED, $advisorTeams)) {
            $managerEmails = [
                ...$managerEmails,
                'murryell.tuppil@insurancemarket.ae',
                'veeral.joshi@insurancemarket.ae',
            ];
        }

        if (in_array(TeamNameEnum::RENEWALS, $advisorTeams)) {
            $managerEmails = [
                ...$managerEmails,
                'mufti.hamid@insurancemarket.ae',
                'veeral.joshi@insurancemarket.ae',
            ];
        }

        if (in_array(TeamNameEnum::ORGANIC, $advisorTeams)) {
            $managerEmails = [
                ...$managerEmails,
                'arsalan.khan@insurancemarket.ae',
                'veeral.joshi@insurancemarket.ae',
            ];
        }

        $managerEmails = array_unique($managerEmails);

        return array_values($managerEmails);
    }

    private function shouldTrackSLA($lead): bool
    {
        return $lead->has_pec_tag;
    }

    private function calculateSLADueTime(Carbon $assignmentTime, float $slaHours, bool $isBusinessHours): Carbon
    {
        $slaMinutes = $slaHours * 60;

        $businessStart = $this->allocationService->getBusinessStartTime();
        $businessEnd = $this->allocationService->getBusinessEndTime();

        // If assigned on weekend, start from next business day
        if ($assignmentTime->isWeekend()) {
            $nextBusinessDay = $this->getNextBusinessDayStart($assignmentTime);

            return $this->addBusinessMinutesFromStart($nextBusinessDay, $slaMinutes);
        }

        // If assigned outside business hours on a weekday
        if (! $isBusinessHours) {
            return $this->getSLADueTimeOutsideBusinessHours($assignmentTime, $slaMinutes, $businessStart);
        }

        return $this->getSLADueTimeWithinBusinessHours($assignmentTime, $slaMinutes, $businessEnd);
    }

    private function getSLADueTimeOutsideBusinessHours(Carbon $assignmentTime, float $slaMinutes, $businessStart): Carbon
    {
        $businessStartTime = $assignmentTime->copy()->setTimeFromTimeString($businessStart);

        // If before business hours, start from same day's business start
        if ($assignmentTime->lessThan($businessStartTime)) {
            return $this->addBusinessMinutesFromStart($businessStartTime, $slaMinutes);
        }

        $nextBusinessDay = $this->getNextBusinessDayStart($assignmentTime);

        return $this->addBusinessMinutesFromStart($nextBusinessDay, $slaMinutes);
    }

    private function getSLADueTimeWithinBusinessHours(Carbon $assignmentTime, float $slaMinutes, $businessEnd): Carbon
    {
        $currentTime = $assignmentTime->copy();
        $endOfCurrentBusinessDay = $currentTime->copy()->setTimeFromTimeString($businessEnd);

        $remainingMinutesToday = $currentTime->diffInMinutes($endOfCurrentBusinessDay, false);

        // If SLA can be completed within current business day
        if ($slaMinutes <= $remainingMinutesToday) {
            return $currentTime->addMinutes($slaMinutes);
        }

        // SLA spills over to next business day(s)
        $remainingSlaMinutes = $slaMinutes - $remainingMinutesToday;

        $nextBusinessDay = $this->getNextBusinessDayStart($currentTime);

        return $this->addBusinessMinutesFromStart($nextBusinessDay, $remainingSlaMinutes);
    }

    private function addBusinessMinutesFromStart(Carbon $startTime, float $minutes): Carbon
    {
        $businessStart = $this->allocationService->getBusinessStartTime();
        $businessEnd = $this->allocationService->getBusinessEndTime();

        [$startHour, $startMinute] = explode(':', $businessStart);
        [$endHour, $endMinute] = explode(':', $businessEnd);

        $startTotalMinutes = ((int) $startHour * 60) + (int) $startMinute;
        $endTotalMinutes = ((int) $endHour * 60) + (int) $endMinute;

        $dailyBusinessMinutes = $endTotalMinutes - $startTotalMinutes;

        if ($dailyBusinessMinutes <= 0) {
            throw new \InvalidArgumentException("Invalid business hours: start time ({$businessStart}) must be before end time ({$businessEnd})");
        }

        $currentTime = $startTime->copy();
        $remainingMinutes = $minutes;

        while ($remainingMinutes > 0) {
            // If remaining minutes fit in current business day
            if ($remainingMinutes <= $dailyBusinessMinutes) {
                return $currentTime->addMinutes($remainingMinutes);
            }

            // Use full business day and move to next business day
            $remainingMinutes -= $dailyBusinessMinutes;
            $currentTime = $this->getNextBusinessDayStart($currentTime);
        }

        return $currentTime;
    }

    private function getNextBusinessDayStart(?Carbon $fromDate = null): Carbon
    {
        $nextBusinessDay = $fromDate ? $fromDate->copy() : now();

        do {
            $nextBusinessDay = $nextBusinessDay->addDay();
        } while ($nextBusinessDay->isWeekend());

        $businessStart = $this->allocationService->getBusinessStartTime();

        return $nextBusinessDay->setTimeFromTimeString($businessStart);
    }

    private function isProduction(): bool
    {
        return config('constants.APP_ENV') == EnvEnum::PRODUCTION;
    }
}
