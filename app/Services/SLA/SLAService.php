<?php

declare(strict_types=1);

namespace App\Services\SLA;

use App\Enums\ApplicationStorageEnums;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteTypes;
use App\Enums\SLAActionTypeEnum;
use App\Enums\SLAStatusEnum;
use App\Models\SLATracking;
use App\Services\AllocationService;
use App\Services\EmailServices\WebEngageService;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SLAService
{
    use SLAable;

    public function __construct(protected AllocationService $allocationService) {}

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

        $slaRecord = SLATracking::create($data);

        LoggerService::info('SLAService - SLA tracking record created', [
            'sla_id' => $slaRecord->id,
            'due_datetime' => $slaRecord->sla_due_at->toDateTimeString(),
            'is_business_hours' => $isBusinessHours,
            'callback_hours' => $callbackHours,
        ]);

        $this->sendCallbackNotification($slaRecord);

        return $slaRecord;
    }

    public function initiateSLATracking(QuoteTypes $quoteType, Model $lead): ?SLATracking
    {
        if (! $this->isLOBEnabled($lead) || $this->hasAnyFinalSLA($lead)) {
            if ($this->hasAnyFinalSLA($lead)) {
                LoggerService::info('SLAService - SLA already reached a final status, so skipping further SLA tracking');
            }

            return null;
        }

        $existingActiveSLA = $this->getActiveSLA($lead);

        if (! $this->shouldTrackSLA($lead)) {
            if ($existingActiveSLA) {
                $existingActiveSLA->markMet(SLAActionTypeEnum::PEC_TAG_REMOVED, 'SLA tracking marked as met because lead is no longer PEC-marked which means customer has already been contacted');
            }

            return null;
        }

        return $this->proceedWithSLATracking($quoteType, $lead, $existingActiveSLA);
    }

    private function proceedWithSLATracking(QuoteTypes $quoteType, Model $lead, ?SLATracking $existingActiveSLA = null): SLATracking
    {
        if ($existingActiveSLA) {
            if ($existingActiveSLA->advisor_id === $lead->advisor_id) {
                LoggerService::info('SLAService - Active SLA already exists for this advisor', [
                    'advisor_id' => $lead->advisor_id,
                    'sla_id' => $existingActiveSLA->id,
                ]);

                return $existingActiveSLA;
            }

            if ($existingActiveSLA->advisor_id !== $lead->advisor_id) {
                $this->markReAssigned($existingActiveSLA, $lead);
            }
        }

        $callbackHours = (float) getAppStorageValueByKey(ApplicationStorageEnums::SLA_CALLBACK_HOURS) ?: 2;
        $assignmentTime = $this->getAssignmentTime($quoteType, $lead);
        $isBusinessHours = $this->isAssignmentTimeWithinBusinessHours($assignmentTime);

        LoggerService::info('SLAService - Starting SLA tracking', [
            'advisor_id' => $lead->advisor_id,
            'assignment_time' => $assignmentTime->toDateTimeString(),
            'is_business_hours' => $isBusinessHours,
            'callback_hours' => $callbackHours,
        ]);

        return $this->createSLA($lead, $assignmentTime, $callbackHours, $isBusinessHours);
    }

    private function meetSLA(Model $lead, SLAActionTypeEnum $actionType, ?string $reason = null): void
    {
        $slaRecord = $this->getActiveSLA($lead);

        if (! $slaRecord) {
            LoggerService::info('SLAService - No active SLA found for this lead');

            return;
        }

        if (Auth::id() != $slaRecord->advisor_id) {
            LoggerService::info('SLAService - SLA tried to be met by someone other than the one assigned to the lead', [
                'sla_id' => $slaRecord->id,
                'quote_status_id' => $lead->quote_status_id,
                'advisor_id' => $slaRecord->advisor_id,
                'logged_in_user_id' => Auth::id(),
            ]);

            return;
        }

        $defaultReason = 'SLA met by advisor action';
        $slaRecord->markMet($actionType, $reason ?? $defaultReason);

        LoggerService::info('SLAService - SLA marked as met', [
            'sla_id' => $slaRecord->id,
            'quote_status_id' => $lead->quote_status_id,
            'completion_time' => now()->toDateTimeString(),
            'reason' => $reason ?? $defaultReason,
        ]);
    }

    public function meetSLAOnStatusUpdate(Model $lead): void
    {
        if (! $this->isLOBEnabled($lead)) {
            return;
        }

        LoggerService::startQuoteLogging($lead, LoggerFeatureEnum::SLA_TRACKING);

        if (in_array($lead->quote_status_id, self::getMeetableQuoteStatuses())) {
            $quoteStatus = $lead->quoteStatus?->text;
            LoggerService::info("SLAService - Status updated to {$quoteStatus}, marking SLA as met", [
                'new_status_id' => $lead->quote_status_id,
            ]);

            $this->meetSLA($lead, SLAActionTypeEnum::STATUS_UPDATED, "Lead status updated to: {$quoteStatus}");
        }
    }

    public function meetSLAOnAMLStatusUpdate(Model $lead): void
    {
        if (! $this->isLOBEnabled($lead)) {
            return;
        }

        LoggerService::startQuoteLogging($lead, LoggerFeatureEnum::SLA_TRACKING);

        if (in_array($lead->aml_status, self::getMeetableAMLStatuses())) {
            LoggerService::info("SLAService - AML status updated to {$lead->aml_status}, marking SLA as met", [
                'new_aml_status' => $lead->aml_status,
            ]);

            $this->meetSLA($lead, SLAActionTypeEnum::AML_STATUS_UPDATED, "Lead AML status updated to: {$lead->aml_status}");
        }
    }

    public function meetSLAOnKYCStatusUpdate(Model $lead): void
    {
        if (! $this->isLOBEnabled($lead)) {
            return;
        }

        LoggerService::startQuoteLogging($lead, LoggerFeatureEnum::SLA_TRACKING);

        if (in_array($lead->kyc_decision, self::getMeetableKYCDecisionStatuses())) {
            LoggerService::info("SLAService - KYC decision updated to {$lead->kyc_decision}, marking SLA as met", [
                'new_kyc_decision' => $lead->kyc_decision,
            ]);

            $this->meetSLA($lead, SLAActionTypeEnum::KYC_STATUS_UPDATED, "Lead KYC decision updated to: {$lead->kyc_decision}");
        }
    }

    public function meetSLAOnEdit(Model $lead, SLAActionTypeEnum $actionType): void
    {
        if (! $this->isLOBEnabled($lead)) {
            return;
        }

        LoggerService::startQuoteLogging($lead, LoggerFeatureEnum::SLA_TRACKING);

        LoggerService::info('SLAService - Lead edited, marking SLA as met', [
            'action_type_label' => $actionType->label(),
        ]);

        $this->meetSLA($lead, $actionType, "Lead edited with action type: {$actionType->label()}");
    }

    private function buildPayload(SLATracking $slaRecord, string $workflowType): array
    {
        $lead = $slaRecord->getLead();
        $advisor = $slaRecord->advisor;

        $remainingHours = (int) ceil(now()->diffInHours($slaRecord->sla_due_at));
        $remainingMinutes = (int) ceil(now()->diffInMinutes($slaRecord->sla_due_at));

        $advisorNameParts = explode(' ', $advisor?->name ?? '', 2);

        return [
            'customerId' => $advisor?->email ?? '',
            'firstName' => $advisorNameParts[0] ?? '',
            'lastName' => $advisorNameParts[1] ?? '',
            'customerEmail' => $advisor?->email ?? '',
            'customerMobile' => ! empty($advisor?->mobile_no) ? '+'.formatMobileNoWithoutPlus($advisor->mobile_no) : '',
            'quoteUID' => $lead->uuid,
            'workflowType' => $workflowType,
            'advisorName' => $advisor?->name,
            'advisorEmail' => $advisor?->email,
            'assignedDateTime' => $slaRecord->assigned_at->toDateTimeString(),
            'currentStatus' => $lead?->quoteStatus?->text,
            'customerName' => "{$lead->first_name} {$lead->last_name}",
            'customerPhone' => $lead->mobile_no,
            'uuid' => $lead->uuid,
            'refID' => $lead->code,
            'SLADueDateTime' => $slaRecord->sla_due_at->toDateTimeString(),
            'remainingHours' => $remainingHours,
            'remainingMinutes' => $remainingMinutes,
        ];
    }

    private function triggerWebEngageEvent(array $payload): bool
    {
        app(WebEngageService::class)->sendEvent($payload['workflowType'], $payload);

        LoggerService::info(self::class.' - triggerWebEngageEvent - Event triggered successfully');

        return true;
    }

    public function sendCallbackNotification(SLATracking $slaRecord): bool
    {
        LoggerService::startQuoteLogging($slaRecord->getLead(), LoggerFeatureEnum::SLA_TRACKING);

        $payload = $this->buildPayload($slaRecord, 'new_pec_la');

        $isSent = $this->triggerWebEngageEvent($payload);

        LoggerService::info('SLAService - Callback notification', [
            'advisor_email' => $slaRecord->advisor?->email,
            'is_sent' => $isSent,
        ]);

        return $isSent;
    }

    public function sendReminderNotification(SLATracking $slaRecord): bool
    {
        LoggerService::startQuoteLogging($slaRecord->getLead(), LoggerFeatureEnum::SLA_TRACKING);

        if ($slaRecord->isReminderSent()) {
            LoggerService::info('SLAService - Reminder notification already sent', [
                'advisor_email' => $slaRecord->advisor?->email,
            ]);

            return false;
        }

        $payload = $this->buildPayload($slaRecord, 'sla_email_notification');

        $isSent = $this->triggerWebEngageEvent($payload);

        if ($isSent) {
            $slaRecord->touch('reminder_sent_at');
        }

        LoggerService::info('SLAService - Reminder notification', [
            'advisor_email' => $slaRecord->advisor?->email,
            'is_sent' => $isSent,
        ]);

        return $isSent;
    }

    public function sendBreachNotification(SLATracking $slaRecord): bool
    {
        LoggerService::startQuoteLogging($slaRecord->getLead(), LoggerFeatureEnum::SLA_TRACKING);

        if ($slaRecord->isBreachEscalated()) {
            LoggerService::info('SLAService - Breach notification already sent', [
                'advisor_email' => $slaRecord->advisor?->email,
            ]);

            return false;
        }

        $advisor = $slaRecord->advisor;

        $payload = $this->buildPayload($slaRecord, 'sla_breach_notification');

        $headEmail = $this->getHeadEmail();
        $payload['customerId'] = $headEmail;
        $payload['firstName'] = '';
        $payload['lastName'] = '';
        $payload['customerEmail'] = $headEmail;
        $payload['customerMobile'] = '';
        $payload['headEmail'] = $headEmail;
        $payload['ccEmails'] = $this->getCCEmails($advisor);
        $payload['breachDateTime'] = now()->toDateTimeString();

        $isSent = $this->triggerWebEngageEvent($payload);

        if ($isSent) {
            $slaRecord->markBreached();
        }

        LoggerService::info('SLAService - Breach escalated to managers', [
            'advisor_email' => $advisor->email,
            'cc_emails' => $payload['ccEmails'],
            'breach_duration' => now()->diffForHumans($slaRecord->sla_due_at),
            'is_sent' => $isSent,
        ]);

        return $isSent;
    }
}
