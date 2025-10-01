<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\SLAStatusEnum;
use App\Models\SLATracking;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class SLAService extends BaseService
{
    public function __construct(protected AllocationService $allocationService) {}

    public function startSLATracking(Model $lead): ?SLATracking
    {
        if (! $this->shouldTrackSLA($lead)) {
            return null;
        }

        LoggerService::startQuoteLogging($lead, LoggerFeatureEnum::SLA_TRACKING);

        $callbackHours = (float) getAppStorageValueByKey(ApplicationStorageEnums::SLA_CALLBACK_HOURS) ?: 2;
        $assignmentTime = now();
        $isBusinessHours = $this->allocationService->isBusinessHours();

        LoggerService::info('SLAService - Starting SLA tracking', [
            'advisor_id' => $lead->advisor_id,
            'assignment_time' => $assignmentTime->toDateTimeString(),
            'is_business_hours' => $isBusinessHours,
            'callback_hours' => $callbackHours,
        ]);

        $data = [
            'trackable_type' => $lead->getMorphClass(),
            'trackable_id' => $lead->id,
            'advisor_id' => $lead->advisor_id,
            'assigned_at' => $assignmentTime,
            'sla_due_at' => $this->calculateSLADueTime($assignmentTime, $callbackHours, $isBusinessHours),
            'is_assigned_during_business_hours' => $isBusinessHours,
            'next_business_day_start' => $isBusinessHours ? null : $this->getNextBusinessDayStart(),
            'status' => SLAStatusEnum::ACTIVE,
        ];

        $slaRecord = SLATracking::create($data);

        LoggerService::info('SLAService - SLA tracking record created', [
            'sla_id' => $slaRecord->id,
            'due_datetime' => $slaRecord->sla_due_at->toDateTimeString(),
            'is_business_hours' => $isBusinessHours,
            'callback_hours' => $callbackHours,
        ]);

        // Dispatch immediate callback notification
        $this->dispatchCallbackNotification($slaRecord);

        return $slaRecord;
    }

    /**
     * Complete SLA tracking when lead reaches final status (mark as MET)
     * Only applies to PEC-marked health leads
     */
    public function completeSLA($lead): void
    {
        // Only complete SLA for eligible leads
        if (! $this->shouldTrackSLA($lead)) {
            return;
        }

        $slaRecord = SLATracking::where('trackable_type', get_class($lead))
            ->where('trackable_id', $lead->id)
            ->where('status', SLAStatusEnum::ACTIVE)
            ->first();

        if ($slaRecord) {
            $slaRecord->markMet();

            LoggerService::info('SLAService - SLA marked as met', [
                'lead_uuid' => $lead->uuid,
                'sla_id' => $slaRecord->id,
                'completion_time' => now()->toDateTimeString(),
            ]);
        }
    }

    /**
     * Cancel active SLA tracking when lead is reassigned
     * Only applies to PEC-marked health leads
     */
    public function cancelActiveSLA($lead): void
    {
        // Only cancel SLA for eligible leads
        if (! $this->shouldTrackSLA($lead)) {
            return;
        }

        $activeSLAs = SLATracking::where('trackable_type', get_class($lead))
            ->where('trackable_id', $lead->id)
            ->where('status', SLAStatusEnum::ACTIVE)
            ->get();

        foreach ($activeSLAs as $slaRecord) {
            $reason = "Lead {$lead->uuid} reassigned from advisor {$slaRecord->advisor_id} to new advisor";
            $slaRecord->markReassigned($reason);

            LoggerService::info('SLAService - SLA marked as reassigned', [
                'lead_uuid' => $lead->uuid,
                'sla_id' => $slaRecord->id,
                'old_advisor_id' => $slaRecord->advisor_id,
                'reason' => $reason,
                'reassigned_time' => now()->toDateTimeString(),
            ]);
        }
    }

    /**
     * Send reminder notification before SLA breach
     */
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

    /**
     * Escalate SLA breach to team lead
     */
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
        $businessEnd = $this->getBusinessEndTime();

        // If assigned outside business hours or on weekends, start from next business day
        if (! $isBusinessHours || $assignmentTime->isWeekend()) {
            $nextBusinessDay = $this->getNextBusinessDayStart($assignmentTime);

            return $this->addBusinessHoursFromStart($nextBusinessDay, $slaHours);
        }

        // Assignment is during business hours
        $currentTime = $assignmentTime->copy();
        $endOfCurrentBusinessDay = $currentTime->copy()->setTimeFromTimeString($businessEnd);

        // Calculate remaining business hours in current day
        $remainingHoursToday = $currentTime->diffInHours($endOfCurrentBusinessDay, false);

        // If SLA can be completed within current business day
        if ($slaHours <= $remainingHoursToday) {
            return $currentTime->addHours($slaHours);
        }

        // SLA spills over to next business day(s)
        $remainingSlaHours = $slaHours - $remainingHoursToday;

        // Move to next business day
        $nextBusinessDay = $this->getNextBusinessDayStart($currentTime);

        return $this->addBusinessHoursFromStart($nextBusinessDay, $remainingSlaHours);
    }

    private function addBusinessHoursFromStart(Carbon $startTime, float $hours): Carbon
    {
        $businessStart = $this->getBusinessStartTime();
        $businessEnd = $this->getBusinessEndTime();

        // Parse time strings to get hours and minutes
        [$startHour, $startMinute] = explode(':', $businessStart);
        [$endHour, $endMinute] = explode(':', $businessEnd);

        // Calculate daily business hours using decimal hours
        $startDecimalHours = (int) $startHour + ((int) $startMinute / 60);
        $endDecimalHours = (int) $endHour + ((int) $endMinute / 60);

        $dailyBusinessHours = $endDecimalHours - $startDecimalHours;

        // Validate business hours
        if ($dailyBusinessHours <= 0) {
            throw new \InvalidArgumentException("Invalid business hours: start time ({$businessStart}) must be before end time ({$businessEnd})");
        }

        $currentTime = $startTime->copy();
        $remainingHours = $hours;

        while ($remainingHours > 0) {
            // If remaining hours fit in current business day
            if ($remainingHours <= $dailyBusinessHours) {
                return $currentTime->addHours($remainingHours);
            }

            // Use full business day and move to next business day
            $remainingHours -= $dailyBusinessHours;
            $currentTime = $this->getNextBusinessDayStart($currentTime);
        }

        return $currentTime;
    }

    private function getNextBusinessDayStart(?Carbon $fromDate = null): Carbon
    {
        $startDate = $fromDate ? $fromDate->copy() : now();
        $nextDay = $startDate->addDay();

        // Skip weekends
        while ($nextDay->isWeekend()) {
            $nextDay = $nextDay->addDay();
        }

        $businessStart = $this->getBusinessStartTime();

        return $nextDay->setTimeFromTimeString($businessStart);
    }

    private function getBusinessStartTime(): string
    {
        return getAppStorageValueByKey(ApplicationStorageEnums::REASSIGNMENT_START_TIME, useCache: true);
    }

    private function getBusinessEndTime(): string
    {
        return getAppStorageValueByKey(ApplicationStorageEnums::REASSIGNMENT_END_TIME, useCache: true);
    }

    /**
     * Dispatch callback notification to advisor
     */
    private function dispatchCallbackNotification(SLATracking $slaRecord): void
    {
        // TODO: Send Bird Email here
    }

    /**
     * Get URL for lead based on type
     * Only handles health quotes since that's all we track
     */
    private function getLeadUrl(SLATracking $slaRecord): string
    {
        $baseUrl = url('/');
        $leadType = $slaRecord->getLeadType();
        $leadUuid = $slaRecord->getLeadUuid();

        return match ($leadType) {
            'health' => "{$baseUrl}/quotes/health/{$leadUuid}",
            default => $baseUrl,
        };
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
