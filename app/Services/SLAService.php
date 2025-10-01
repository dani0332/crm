<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use App\Models\SLATracking;
use App\Enums\SLAStatusEnum;
use App\Mail\SLABreachEscalation;
use Illuminate\Support\Facades\Mail;
use App\Enums\ApplicationStorageEnums;
use App\Services\Logger\LoggerService;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Events\SLACallbackNotification;
use App\Events\SLAReminderNotification;
use Illuminate\Database\Eloquent\Model;

class SLAService extends BaseService
{
    public function __construct(protected AllocationService $allocationService) { }

    public function startSLATracking(Model $lead): ?SLATracking
    {
        if (! $this->shouldTrackSLA($lead)) {
            return null;
        }

        LoggerService::startQuoteLogging($lead, LoggerFeatureEnum::SLA_TRACKING);

        LoggerService::info('SLAService - Starting SLA tracking');

        $callbackHours = (float) getAppStorageValueByKey(ApplicationStorageEnums::SLA_CALLBACK_HOURS) ?: 2;
        $assignmentTime = now();
        $isBusinessHours = $this->allocationService->isBusinessHours();

        $slaRecord = SLATracking::create([
            'trackable_type' => $lead->getMorphClass(),
            'trackable_id' => $lead->id,
            'advisor_id' => $lead->advisor_id,
            'assigned_at' => $assignmentTime,
            'sla_due_at' => $this->calculateSLADueTime($assignmentTime, $callbackHours, $isBusinessHours),
            'is_assigned_during_business_hours' => $isBusinessHours,
            'next_business_day_start' => $isBusinessHours ? null : $this->getNextBusinessDayStart(),
            'status' => SLAStatusEnum::ACTIVE,
        ]);

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

        event(new SLAReminderNotification(
            $slaRecord->getLeadUuid(),
            $slaRecord->advisor_id,
            $slaRecord->sla_due_at,
            $this->getLeadUrl($slaRecord),
            $timeRemaining
        ));

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
            Mail::to($teamLead->email)->send(new SLABreachEscalation($slaRecord));

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

    /**
     * Calculate SLA due time considering business hours
     */
    private function calculateSLADueTime(Carbon $assignmentTime, float $slaHours, bool $isBusinessHours): Carbon
    {
        if ($isBusinessHours) {
            return $this->addBusinessHours($assignmentTime, $slaHours);
        } else {
            // If assigned outside business hours, SLA starts from next business day
            $nextBusinessStart = $this->getNextBusinessDayStart();

            return $this->addBusinessHours($nextBusinessStart, $slaHours);
        }
    }

    /**
     * Add business hours to a datetime, respecting working hours
     */
    private function addBusinessHours(Carbon $startTime, float $hours): Carbon
    {
        $businessStart = $this->getBusinessStartTime();
        $businessEnd = $this->getBusinessEndTime();

        $businessStartCarbon = Carbon::createFromFormat('H:i', $businessStart);
        $businessEndCarbon = Carbon::createFromFormat('H:i', $businessEnd);
        $dailyBusinessHours = $businessEndCarbon->diffInHours($businessStartCarbon);

        $fullDays = floor($hours / $dailyBusinessHours);
        $remainingHours = $hours - ($fullDays * $dailyBusinessHours);

        $dueTime = $startTime->copy();

        // Add full business days
        for ($i = 0; $i < $fullDays; $i++) {
            $dueTime = $this->getNextBusinessDay($dueTime);
        }

        // Add remaining hours within business day
        $dueTime = $dueTime->addHours($remainingHours);

        // Ensure we don't exceed business hours for the day
        $endOfBusinessDay = $dueTime->copy()->setTimeFromTimeString($businessEnd);
        if ($dueTime->greaterThan($endOfBusinessDay)) {
            $overflow = $dueTime->diffInHours($endOfBusinessDay);
            $dueTime = $this->getNextBusinessDay($endOfBusinessDay);
            $dueTime = $dueTime->setTimeFromTimeString($businessStart)->addHours($overflow);
        }

        return $dueTime;
    }

    /**
     * Get next business day start time
     */
    private function getNextBusinessDayStart(): Carbon
    {
        $tomorrow = now()->addDay();

        // Skip weekends
        while ($tomorrow->isWeekend()) {
            $tomorrow = $tomorrow->addDay();
        }

        $businessStart = $this->getBusinessStartTime();

        return $tomorrow->setTimeFromTimeString($businessStart);
    }

    /**
     * Get next business day from given date
     */
    private function getNextBusinessDay(Carbon $date): Carbon
    {
        $nextDay = $date->copy()->addDay();

        // Skip weekends
        while ($nextDay->isWeekend()) {
            $nextDay = $nextDay->addDay();
        }

        return $nextDay;
    }

    /**
     * Get business start time from configuration
     */
    private function getBusinessStartTime(): string
    {
        return $this->allocationService->getAppStorageValueByKey('REASSIGNMENT_START_TIME') ?? '09:00';
    }

    /**
     * Get business end time from configuration
     */
    private function getBusinessEndTime(): string
    {
        return $this->allocationService->getAppStorageValueByKey('REASSIGNMENT_END_TIME') ?? '18:00';
    }

    /**
     * Dispatch callback notification to advisor
     */
    private function dispatchCallbackNotification(SLATracking $slaRecord): void
    {
        event(new SLACallbackNotification(
            $slaRecord->getLeadUuid(),
            $slaRecord->advisor_id,
            $slaRecord->sla_due_at,
            $this->getLeadUrl($slaRecord)
        ));
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
