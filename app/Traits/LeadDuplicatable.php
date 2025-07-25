<?php

namespace App\Traits;

use App\Enums\QuoteTypes;
use App\Models\User;
use App\Services\Logger\LoggerService;

trait LeadDuplicatable
{
    protected bool $hasDuplicateLead = false;
    protected ?string $existingRecordUuid = null;

    protected function resolveDuplicateLeadInfo(): void
    {
        $quoteDetail = $this->lead->quoteDetail;

        if ($quoteDetail) {
            $this->hasDuplicateLead = (bool) ($quoteDetail->has_duplicate_lead ?? false);
            $this->existingRecordUuid = $quoteDetail->existing_record_uuid ?? null;

            LoggerService::info(self::class.' - resolveDuplicateLeadInfo: Resolved from database', extra: [
                'quote_type' => $this->quoteType->value,
                'quote_uuid' => $this->uuid,
                'has_duplicate_lead' => $this->hasDuplicateLead,
                'existing_record_uuid' => $this->existingRecordUuid,
            ]);
        }
    }

    protected function getAdvisorForDuplicateLeadAssignment()
    {
        LoggerService::info(self::class.' - getAdvisorForDuplicateLeadAssignment: Starting duplicate lead check', extra: [
            'quote_type' => $this->quoteType->value,
            'existing_record_uuid' => $this->existingRecordUuid,
        ]);

        if (empty($this->existingRecordUuid)) {
            LoggerService::info(self::class.' - getAdvisorForDuplicateLeadAssignment: No existingRecordUuid provided');

            return null;
        }

        $previousLead = $this->quoteType->model()
            ->where('uuid', $this->existingRecordUuid)
            ->first();

        if (! $previousLead) {
            LoggerService::info(self::class.' - getAdvisorForDuplicateLeadAssignment: No previous lead found with UUID', extra: [
                'existing_record_uuid' => $this->existingRecordUuid,
            ]);

            return null;
        }

        LoggerService::info(self::class.' - getAdvisorForDuplicateLeadAssignment: Found previous lead', extra: [
            'previous_lead_id' => $previousLead->id,
            'previous_lead_uuid' => $previousLead->uuid,
            'previous_advisor_id' => $previousLead->advisor_id,
        ]);

        if (empty($previousLead->advisor_id)) {
            LoggerService::info(self::class.' - getAdvisorForDuplicateLeadAssignment: Previous lead has no advisor assigned');

            return null;
        }

        $advisor = User::find($previousLead->advisor_id);
        $this->isMaxCapReached($advisor, $this->getQuoteTypeId());

        if (! $advisor) {
            LoggerService::info(self::class.' - getAdvisorForDuplicateLeadAssignment: Previous advisor not found');

            return null;
        }

        if (! $advisor->is_active) {
            LoggerService::info(self::class.' - getAdvisorForDuplicateLeadAssignment: Previous advisor is not active');

            return null;
        }

        $validStatuses = $this->getValidAdvisorStatuses();
        $isOnLeave = ! in_array($advisor->status, $validStatuses);
        if ($isOnLeave) {
            LoggerService::info(self::class.' - getAdvisorForDuplicateLeadAssignment: Previous advisor status not valid for current time, will use ILA logic', extra: [
                'advisor_id' => $advisor->id,
                'advisor_name' => $advisor->name,
                'advisor_status' => $advisor->status,
                'valid_statuses' => $validStatuses,
                'is_business_hours' => $this->isBusinessHours(),
            ]);

            return null;
        }

        $isMaxCapReached = $this->isMaxCapReached($advisor, $this->getQuoteTypeId());
        if ($isMaxCapReached) {
            LoggerService::info(self::class.' - getAdvisorForDuplicateLeadAssignment: Previous advisor has reached max capacity, will use normal allocation', extra: [
                'advisor_id' => $advisor->id,
                'advisor_name' => $advisor->name,
                'allocation_count' => $advisor->leadAllocation?->allocation_count ?? 'N/A',
                'max_capacity' => $advisor->leadAllocation?->max_capacity ?? 'N/A',
            ]);

            return null;
        }

        LoggerService::info(self::class.' - getAdvisorForDuplicateLeadAssignment: Will assign to previous advisor', extra: [
            'advisor_id' => $advisor->id,
            'advisor_name' => $advisor->name,
            'advisor_status' => $advisor->status,
            'allocation_count' => $advisor->leadAllocation?->allocation_count ?? 'N/A',
            'max_capacity' => $advisor->leadAllocation?->max_capacity ?? 'N/A',
        ]);

        return $advisor;
    }

    protected function shouldHandleDuplicateLead(): bool
    {
        $eligibleTypes = [
            QuoteTypes::HOME,
            QuoteTypes::CORPLINE,
            QuoteTypes::PET,
            QuoteTypes::YACHT,
            QuoteTypes::CYCLE,
            QuoteTypes::GROUP_MEDICAL,
            QuoteTypes::LIFE,
        ];

        return in_array($this->quoteType, $eligibleTypes);
    }
}
