<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteTypes;
use App\Enums\SLAStatusEnum;
use App\Models\SLATracking;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class SLAService extends BaseService
{
    public function __construct(protected AllocationService $allocationService) {}

    private function getActiveSLA(Model $lead): ?SLATracking
    {
        return SLATracking::byLead($lead)->active()->first();
    }

    private function markReAssigned(SLATracking $existingActiveSLA, Model $lead): void
    {
        $reason = "Lead {$lead->uuid} reassigned from advisor {$existingActiveSLA->advisor?->email} to advisor {$lead->advisor?->email}";
        $existingActiveSLA->markReassigned($reason);

        LoggerService::info('SLAService - Previous SLA marked as reassigned', [
            'old_sla_id' => $existingActiveSLA->id,
            'old_advisor_id' => $existingActiveSLA->advisor_id,
            'new_advisor_id' => $lead->advisor_id,
            'reason' => $reason,
        ]);
    }

    private function createSLA(Model $lead, Carbon $assignmentTime, float $callbackHours, bool $isBusinessHours): SLATracking
    {
        $data = [
            'trackable_type' => $lead->getMorphClass(),
            'trackable_id' => $lead->id,
            'advisor_id' => $lead->advisor_id,
            'assigned_at' => $assignmentTime,
            'sla_due_at' => $this->calculateSLADueTime($assignmentTime, $callbackHours, $isBusinessHours),
            'status' => SLAStatusEnum::ACTIVE,
        ];
        dd($data);

        $slaRecord = SLATracking::create($data);

        LoggerService::info('SLAService - SLA tracking record created', [
            'sla_id' => $slaRecord->id,
            'due_datetime' => $slaRecord->sla_due_at->toDateTimeString(),
            'is_business_hours' => $isBusinessHours,
            'callback_hours' => $callbackHours,
        ]);

        return $slaRecord;
    }

    private function getAssignmentTime(QuoteTypes $quoteType, Model $lead): Carbon
    {
        $assignmentTime = Carbon::parse($quoteType->detailModel()->where($lead->getForeignKey(), $lead->id)->value('advisor_assigned_date') ?? now());

        return now() > $assignmentTime ? now() : $assignmentTime;
    }

    private function isAssignmentTimeWithinBusinessHours(Carbon $assignmentTime): bool
    {
        $businessStart = Carbon::createFromFormat('H:i', $this->allocationService->getBusinessStartTime());
        $businessEnd = Carbon::createFromFormat('H:i', $this->allocationService->getBusinessEndTime());

        return $assignmentTime->isBetween($businessStart, $businessEnd);
    }

    public function initiateSLATracking(QuoteTypes $quoteType, Model $lead): ?SLATracking
    {
        // $existingActiveSLA = $this->getActiveSLA($lead);

        // if (! $this->shouldTrackSLA($lead)) {
        //     if ($existingActiveSLA) {
        //         $existingActiveSLA->markCanceled();
        //     }

        //     return null;
        // }

        // LoggerService::startQuoteLogging($lead, LoggerFeatureEnum::SLA_TRACKING);

        // if ($existingActiveSLA) {
        //     if ($existingActiveSLA->advisor_id === $lead->advisor_id) {
        //         LoggerService::info('SLAService - Active SLA already exists for this advisor', [
        //             'advisor_id' => $lead->advisor_id,
        //             'sla_id' => $existingActiveSLA->id,
        //         ]);

        //         return $existingActiveSLA;
        //     }

        //     if ($existingActiveSLA->advisor_id !== $lead->advisor_id) {
        //         $this->markReAssigned($existingActiveSLA, $lead);
        //     }
        // }

        $callbackHours = (float) getAppStorageValueByKey(ApplicationStorageEnums::SLA_CALLBACK_HOURS) ?: 2;
        $assignmentTime = $this->getAssignmentTime($quoteType, $lead);
        $isBusinessHours = $this->isAssignmentTimeWithinBusinessHours($assignmentTime);

        LoggerService::info('SLAService - Starting SLA tracking', [
            'advisor_id' => $lead->advisor_id,
            'assignment_time' => $assignmentTime->toDateTimeString(),
            'is_business_hours' => $isBusinessHours,
            'callback_hours' => $callbackHours,
        ]);

        $slaRecord = $this->createSLA($lead, $assignmentTime, $callbackHours, $isBusinessHours);

        // Dispatch immediate callback notification
        $this->dispatchCallbackNotification($slaRecord);

        return $slaRecord;
    }

    public function meetSLA(Model $lead): void
    {
        if (! $this->shouldTrackSLA($lead)) {
            return;
        }

        $slaRecord = $this->getActiveSLA($lead);

        if ($slaRecord) {
            $slaRecord->markMet();

            LoggerService::info('SLAService - SLA marked as met', [
                'lead_uuid' => $lead->uuid,
                'sla_id' => $slaRecord->id,
                'quote_status_id' => $lead->quote_status_id,
                'completion_time' => now()->toDateTimeString(),
            ]);
        }
    }

    public function sendReminderNotification(SLATracking $slaRecord): void
    {
        $timeRemaining = (int) now()->diffInMinutes($slaRecord->sla_due_at);

        // TODO: Send Bird Email here

        $slaRecord->touch('reminder_sent_at');

        LoggerService::info('SLAService - Reminder notification sent', [
            'lead_uuid' => $slaRecord->getLeadUuid(),
            'advisor_id' => $slaRecord->advisor_id,
            'time_remaining_minutes' => $timeRemaining,
        ]);
    }

    public function escalateBreach(SLATracking $slaRecord): void
    {
        $advisor = $slaRecord->advisor;
        $teamLead = $this->getTeamLead($advisor);

        if ($teamLead) {
            // Mark as breached
            $slaRecord->markBreached();

            // Send escalation email
            // TODO: Send Bird Email here

            LoggerService::info('SLAService - Breach escalated to team lead', [
                'lead_uuid' => $slaRecord->getLeadUuid(),
                'advisor_id' => $slaRecord->advisor_id,
                'team_lead_email' => $teamLead->email,
                'breach_duration' => now()->diffForHumans($slaRecord->sla_due_at),
            ]);
        } else {
            LoggerService::warning('SLAService - No team lead found for escalation', [
                'advisor_id' => $slaRecord->advisor_id,
            ]);
        }
    }

    private function calculateSLADueTime(Carbon $assignmentTime, float $slaHours, bool $isBusinessHours): Carbon
    {
        $slaMinutes = $slaHours * 60;

        $businessEnd = $this->allocationService->getBusinessEndTime();

        // If assigned outside business hours or on weekends, start from next business day
        if (! $isBusinessHours || $assignmentTime->isWeekend()) {
            $nextBusinessDay = $this->getNextBusinessDayStart($assignmentTime);

            return $this->addBusinessMinutesFromStart($nextBusinessDay, $slaMinutes);
        }

        // Assignment is during business hours
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

    /**
     * Dispatch callback notification to advisor
     */
    private function dispatchCallbackNotification(SLATracking $slaRecord): void
    {
        $lead = $slaRecord->getLead();

        $payload = [
            'workflowType' => 'new_pec_la',
            'advisorName' => $slaRecord->advisor?->name,
            'advisorEmail' => $slaRecord->advisor?->email,
            'assignedDateTime' => $slaRecord->assigned_at->toDateTimeString(),
            'currentStatus' => $slaRecord->getLead()->quoteStatus?->text,
            'customerName' => $slaRecord->getLead()->first_name.' '.$slaRecord->getLead()->last_name,
            'customerEmail' => '',
            'customerPhone' => '',
            'refID' => '',
            'SLADueDateTime' => '',
        ];

        $customerNotificationWorkflow = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_CUSTOMER_NOTIFY_UNAVAILABLE_ADVIOSR_WORKFLOW, useCache: true);
        if (! empty($customerNotificationWorkflow)) {
            app(BirdService::class)->triggerWebHookRequest($customerNotificationWorkflow, (object) $payload);
            LoggerService::info(self::class.' - sendIntroAndReassignEmail - Webhook request sent to: '.$customerNotificationWorkflow.' with Ref-ID: '.$lead->uuid.' | Time:'.now());
        } else {
            LoggerService::info(self::class.'- sendIntroAndReassignEmail - Webhook URL not found in storage');
        }
    }

    /**
     * Get team lead for advisor
     */
    private function getTeamLead($advisor)
    {
        // Implementation based on organizational structure
        // This could be based on roles, departments, or a specific manager relationship

        // For now, return a default escalation email or the first admin user
        return \App\Models\User::where('role', 'team_lead')
            ->orWhere('role', 'admin')
            ->first();
    }

    private function shouldTrackSLA($lead): bool
    {
        return $lead->has_pec_tag;
    }
}
