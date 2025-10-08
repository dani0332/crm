<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\SLAActionTypeEnum;
use App\Enums\SLAStatusEnum;
use App\Enums\TeamNameEnum;
use App\Models\HealthQuote;
use App\Models\SLATracking;
use App\Models\User;
use App\Services\Logger\LoggerService;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SLAService extends BaseService
{
    use TeamHierarchyTrait;

    public function __construct(protected AllocationService $allocationService) {}

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

    public function initiateSLATracking(QuoteTypes $quoteType, Model $lead): ?SLATracking
    {
        if (! $this->isLOBEnabled($lead)) {
            return null;
        }

        if ($this->hasAnyFinalSLA($lead)) {
            LoggerService::info('SLAService - SLA already reached a final status, so skipping further SLA tracking');

            return null;
        }

        $existingActiveSLA = $this->getActiveSLA($lead);

        if (! $this->shouldTrackSLA($lead)) {
            if ($existingActiveSLA) {
                $existingActiveSLA->markMet(SLAActionTypeEnum::PEC_TAG_REMOVED, 'SLA tracking marked as met because lead is no longer PEC-marked which means customer has already been contacted');
            }

            return null;
        }

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
            $businessStartTime = $assignmentTime->copy()->setTimeFromTimeString($businessStart);
            $businessEndTime = $assignmentTime->copy()->setTimeFromTimeString($businessEnd);

            // If before business hours, start from same day's business start
            if ($assignmentTime->lessThan($businessStartTime)) {
                return $this->addBusinessMinutesFromStart($businessStartTime, $slaMinutes);
            }

            // If after business hours, start from next business day
            if ($assignmentTime->greaterThanOrEqualTo($businessEndTime)) {
                $nextBusinessDay = $this->getNextBusinessDayStart($assignmentTime);

                return $this->addBusinessMinutesFromStart($nextBusinessDay, $slaMinutes);
            }
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

    private function buildPayload(SLATracking $slaRecord, $workflowType): array
    {
        $lead = $slaRecord->getLead();

        $remainingHours = (int) ceil(now()->diffInHours($slaRecord->sla_due_at));
        $remainingMinutes = (int) ceil(now()->diffInMinutes($slaRecord->sla_due_at));

        return [
            'workflowType' => $workflowType,
            'advisorName' => $slaRecord->advisor?->name,
            'advisorEmail' => $slaRecord->advisor?->email,
            'assignedDateTime' => $slaRecord->assigned_at->toDateTimeString(),
            'currentStatus' => $lead?->quoteStatus?->text,
            'customerName' => "{$lead->first_name} {$lead->last_name}",
            'customerEmail' => $lead->email,
            'customerPhone' => $lead->mobile_no,
            'uuid' => $lead->uuid,
            'refID' => $lead->code,
            'SLADueDateTime' => $slaRecord->sla_due_at->toDateTimeString(),
            'remainingHours' => $remainingHours,
            'remainingMinutes' => $remainingMinutes,
        ];
    }

    private function triggerBirdWorkflow(array $payload): bool
    {
        $customerNotificationWorkflow = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_CUSTOMER_NOTIFY_UNAVAILABLE_ADVIOSR_WORKFLOW, useCache: true);
        if (! empty($customerNotificationWorkflow)) {
            $response = app(BirdService::class)->triggerWebHookRequest($customerNotificationWorkflow, (object) $payload);

            if ($response->status_code >= 200 && $response->status_code < 300) {
                LoggerService::info(self::class.' - triggerBirdWorkflow - Webhook Request Sent');

                return true;
            } else {
                LoggerService::info(self::class.'- triggerBirdWorkflow - Webhook Request Failed');

                return false;
            }
        } else {
            LoggerService::info(self::class.'- dispatchCallbackNotification - Webhook URL not found in storage');

            return false;
        }
    }

    public function sendCallbackNotification(SLATracking $slaRecord): bool
    {
        LoggerService::startQuoteLogging($slaRecord->getLead(), LoggerFeatureEnum::SLA_TRACKING);

        $payload = $this->buildPayload($slaRecord, 'new_pec_la');

        $isSent = $this->triggerBirdWorkflow($payload);

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

        $isSent = $this->triggerBirdWorkflow($payload);

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

        $payload['headEmail'] = $this->getHeadEmail();
        $payload['ccEmails'] = $this->getCCEmails($advisor);
        $payload['breachDateTime'] = now()->toDateTimeString();

        $isSent = $this->triggerBirdWorkflow($payload);

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

    private function getHeadEmail(): string
    {
        return 'agatha.alicdan@insurancemarket.ae';
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
        $advisorTeams = $advisor->teams->filter(function ($team) {
            return $team->is_active && $team->type === 'Team';
        })->pluck('name')->toArray();

        $hasPCPTeam = in_array(TeamNameEnum::PCP, $advisorTeams);
        $hasRMTeam = in_array(TeamNameEnum::RM_SPEED, $advisorTeams);
        $hasRenewalsTeam = in_array(TeamNameEnum::RENEWALS, $advisorTeams);
        $hasOrganicTeam = in_array(TeamNameEnum::ORGANIC, $advisorTeams);

        $managerEmails = [];

        if ($hasPCPTeam) {
            $managerEmails = [
                ...$managerEmails,
                'moinuddin.lakdawala@insurancemarket.ae',
            ];
        }

        if ($hasRMTeam) {
            $managerEmails = [
                ...$managerEmails,
                'murryell.tuppil@insurancemarket.ae',
                'veeral.joshi@insurancemarket.ae',
            ];
        }

        if ($hasRenewalsTeam) {
            $managerEmails = [
                ...$managerEmails,
                'mufti.hamid@insurancemarket.ae',
                'veeral.joshi@insurancemarket.ae',
            ];
        }

        if ($hasOrganicTeam) {
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
}
