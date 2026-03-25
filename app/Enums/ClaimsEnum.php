<?php

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * Claims Enum
 *
 * Consolidates all claim-related constants, statuses, types, and configuration values
 * that were previously scattered across controllers, services, and models.
 */
enum ClaimsEnum: string
{
    case CLAIM_STATUSES_STATUS_KEY = 'statuses';
    case CLAIM_STATUSES_SUB_STATUS_KEY = 'sub-statuses';
    case CLAIM_STATUSES_COMPLAINT_STATUS_KEY = 'complaint-statuses';

    case CLAIM_TYPES_KEY = 'claim-types';
    case CLAIM_REQUEST_TYPES_KEY = 'claim-request-types';
    case CLAIM_SERVICE_TYPES_KEY = 'claim-service-types';
    case CLAIM_STATUS_ACCESS_TYPES_KEY = 'claim-status-access-types';
    case CLAIM_TPA_OPTIONS_KEY = 'claim-tpa-options';
    case CLAIM_REIMBURSEMENT_OPTIONS_KEY = 'claim-reimbursement-option';

    // Claim Types Codes
    case CLAIM_TYPE_OWN_DAMAGE_CLAIM_CODE = 'own-damage-claim';
    case CLAIM_TYPE_RECOVERABLE_CLAIM_CODE = 'recoverable-claim';
    case CLAIM_TYPE_UNKNOWN_DAMAGE_CLAIM_CODE = 'unknown-damage-claim';
    case CLAIM_TYPE_WATER_DAMAGE_CODE = 'water-damage';
    case CLAIM_TYPE_THEFT_CODE = 'theft';
    case CLAIM_TYPE_FIRE_ARSON_CODE = 'fire-arson';
    case CLAIM_TYPE_WINDSCREEN_ONLY_CODE = 'windscreen-only';

    // Claim Request Type Codes
    case CLAIM_REQUEST_TYPE_REIMBURSEMENT_CODE = 'reimbursement';
    case CLAIM_REQUEST_TYPE_PENDING_APPROVALS_CODE = 'pending-approvals';
    case CLAIM_REQUEST_TYPE_ASK_A_QUESTION_CODE = 'ask-a-question';

    // Claim Service Type Codes
    case CLAIM_SERVICE_TYPE_IN_PATIENT_REQUEST_CODE = 'in-patient-request';
    case CLAIM_SERVICE_TYPE_OUT_PATIENT_REQUEST_CODE = 'out-patient-request';
    case CLAIM_SERVICE_TYPE_OTHERS_REQUEST_CODE = 'others';

    // Claim Request Access Type Codes
    case CLAIM_REQUEST_ACCESS_TYPE_SYSTEM_GENERATED_CODE = 'system-generated';
    case CLAIM_REQUEST_ACCESS_TYPE_MANUAL_CODE = 'manual';

    /* Claim Request Status */
    case CLAIM_STATUS_OPEN = 'open';
    case CLAIM_STATUS_REOPEN = 're-open';
    case CLAIM_STATUS_CLOSED = 'close';

    // Complaint Status Codes
    case CLAIM_STATUS_OPEN_COMPLAINT = 'complaint open';
    case CLAIM_STATUS_CLOSED_COMPLAINT = 'complaint close';

    // General Claim Sub Statuses
    case CLAIM_SUB_STATUS_NEW_CLAIM = 'new claim';
    case CLAIM_SUB_STATUS_CLAIM_INITIATED = 'claim initiated';
    case CLAIM_SUB_STATUS_CLAIM_REGISTERED = 'claim registered';
    case CLAIM_SUB_STATUS_CLAIM_REGISTERED_AWAITING_INSPECTION = 'claim registered and awaiting inspection';
    case CLAIM_SUB_STATUS_ESTIMATE_UNDER_REVIEW = 'estimate under review';
    case CLAIM_SUB_STATUS_SURVEY_IN_PROGRESS = 'survey in progress';
    case CLAIM_SUB_STATUS_CLAIM_UNDER_REVIEW = 'claim under review';
    case CLAIM_SUB_STATUS_CLAIM_APPROVED = 'claim approved';
    case CLAIM_SUB_STATUS_CLAIM_PARTIALLY_APPROVED = 'claim partially approved';
    case CLAIM_SUB_STATUS_CLAIM_DENIED = 'claim denied';
    case CLAIM_SUB_STATUS_CLAIM_WITHDRAWN = 'claim withdrawn';
    case CLAIM_SUB_STATUS_CLAIM_CLOSED = 'claim closed';
    case CLAIM_SUB_STATUS_CLAIM_PAID = 'claim paid';
    case CLAIM_SUB_STATUS_SETTLEMENT_IN_PROGRESS = 'settlement in progress';

    // Motor/Car Specific Claim Sub Statuses
    case CLAIM_SUB_STATUS_REPAIR_APPROVED_AND_WORK_IN_PROGRESS = 'repair approved & work in progress';
    case CLAIM_SUB_STATUS_PARTS_ORDERED = 'parts ordered';
    case CLAIM_SUB_STATUS_PARTS_ON_BACKORDER = 'parts on backorder';
    case CLAIM_SUB_STATUS_PARTS_DELAYED = 'parts delayed';
    case CLAIM_SUB_STATUS_PARTS_ARRIVED_AND_WORK_IN_PROGRESS = 'parts arrived & work in progress';
    case CLAIM_SUB_STATUS_HIRE_CAR_REQUESTED = 'hire car requested';
    case CLAIM_SUB_STATUS_HIRE_CAR_APPROVED = 'hire car approved';
    case CLAIM_SUB_STATUS_HIRE_CAR_REFUND_IN_PROGRESS = 'hire car refund in progress';
    case CLAIM_SUB_STATUS_CAR_READY_FOR_COLLECTION = 'car ready for collection';
    case CLAIM_SUB_STATUS_REPAIR_COMPLETED = 'repair completed';
    case CLAIM_SUB_STATUS_REPAIR_COMPLETED_AND_CLAIM_SETTLED = 'repair completed and claim settled';
    case CLAIM_SUB_STATUS_TOTAL_LOSS_APPROVED = 'total loss approved';
    case CLAIM_SUB_STATUS_TOTAL_LOSS_OFFER_LETTER_SHARED = 'total loss offer letter shared';
    case CLAIM_SUB_STATUS_TOTAL_LOSS_PAYMENT_IN_PROGRESS = 'total loss payment in progress';
    case CLAIM_SUB_STATUS_TOTAL_LOSS_PAID_AND_CLAIM_SETTLED = 'total loss paid and claim settled';
    case CLAIM_SUB_STATUS_CASH_LOSS_APPROVED = 'cash loss approved';
    case CLAIM_SUB_STATUS_CASH_LOSS_PAYMENT_IN_PROGRESS = 'cash loss payment in progress';
    case CLAIM_SUB_STATUS_CASH_LOSS_PAID_AND_CLAIM_SETTLED = 'cash loss paid and claim settled';

    // Document Related Statuses
    case CLAIM_SUB_STATUS_ADDITIONAL_DOCUMENTS_AWAITED = 'additional documents awaited';
    case CLAIM_SUB_STATUS_DOCUMENTS_UPLOADED = 'documents uploaded';
    case CLAIM_SUB_STATUS_CLAIM_PENDING_FOR_ADDITIONAL_INFORMATION = 'claim pending for additional information';

    // Health Specific Claim Sub Statuses
    case CLAIM_SUB_STATUS_CLAIM_REPROCESSING = 'claim reprocessing';

    // Request Related Statuses (Health - Pending Approvals)
    case CLAIM_SUB_STATUS_NEW_REQUEST = 'new request';
    case CLAIM_SUB_STATUS_UNDER_EVALUATION = 'under evaluation';
    case CLAIM_SUB_STATUS_UNDER_RE_EVALUATION = 'under re-evaluation';
    case CLAIM_SUB_STATUS_PARTIALLY_APPROVED = 'partially approved';
    case CLAIM_SUB_STATUS_REQUEST_DENIED = 'request denied';
    case CLAIM_SUB_STATUS_REQUEST_APPROVED = 'request approved';

    // Question Related Statuses (Health - Ask a Question)
    case CLAIM_SUB_STATUS_UNDER_REVIEW = 'under review';
    case CLAIM_SUB_STATUS_ANSWERED_AND_CLOSED = 'answered & closed';

    // Legacy Status Constants (for backward compatibility)
    case CLAIM_SUB_STATUS_PAYMENT_INITIATED = 'payment initiated';
    case CLAIM_SUB_STATUS_PAYMENT_COMPLETED = 'payment completed';

    /**
     * Get all claim type codes
     */
    public static function getClaimTypeCodes(): array
    {
        return [
            self::CLAIM_TYPE_OWN_DAMAGE_CLAIM_CODE->value,
            self::CLAIM_TYPE_RECOVERABLE_CLAIM_CODE->value,
            self::CLAIM_TYPE_UNKNOWN_DAMAGE_CLAIM_CODE->value,
            self::CLAIM_TYPE_WATER_DAMAGE_CODE->value,
            self::CLAIM_TYPE_THEFT_CODE->value,
            self::CLAIM_TYPE_FIRE_ARSON_CODE->value,
            self::CLAIM_TYPE_WINDSCREEN_ONLY_CODE->value,
        ];
    }

    /**
     * Get all claim request type codes
     */
    public static function getClaimRequestTypeCodes(): array
    {
        return [
            self::CLAIM_REQUEST_TYPE_REIMBURSEMENT_CODE->value,
            self::CLAIM_REQUEST_TYPE_PENDING_APPROVALS_CODE->value,
            self::CLAIM_REQUEST_TYPE_ASK_A_QUESTION_CODE->value,
        ];
    }

    /**
     * Get all claim Service Type codes
     */
    public static function getClaimServiceTypeCodes(): array
    {
        return [
            self::CLAIM_SERVICE_TYPE_IN_PATIENT_REQUEST_CODE->value,
            self::CLAIM_SERVICE_TYPE_OUT_PATIENT_REQUEST_CODE->value,
            self::CLAIM_SERVICE_TYPE_OTHERS_REQUEST_CODE->value,
        ];
    }

    /**
     * Get all claim request access type codes
     */
    public static function getClaimRequestAccessTypeCodes(): array
    {
        return [
            self::CLAIM_REQUEST_ACCESS_TYPE_SYSTEM_GENERATED_CODE->value,
            self::CLAIM_REQUEST_ACCESS_TYPE_MANUAL_CODE->value,
        ];
    }

    /**
     * Get all claim sub status values
     */
    public static function getClaimSubStatuses(): array
    {
        return [
            // General Claim Sub Statuses
            self::CLAIM_SUB_STATUS_NEW_CLAIM->value,
            self::CLAIM_SUB_STATUS_CLAIM_INITIATED->value,
            self::CLAIM_SUB_STATUS_CLAIM_REGISTERED->value,
            self::CLAIM_SUB_STATUS_CLAIM_REGISTERED_AWAITING_INSPECTION->value,
            self::CLAIM_SUB_STATUS_ESTIMATE_UNDER_REVIEW->value,
            self::CLAIM_SUB_STATUS_SURVEY_IN_PROGRESS->value,
            self::CLAIM_SUB_STATUS_CLAIM_UNDER_REVIEW->value,
            self::CLAIM_SUB_STATUS_CLAIM_APPROVED->value,
            self::CLAIM_SUB_STATUS_CLAIM_PARTIALLY_APPROVED->value,
            self::CLAIM_SUB_STATUS_CLAIM_DENIED->value,
            self::CLAIM_SUB_STATUS_CLAIM_WITHDRAWN->value,
            self::CLAIM_SUB_STATUS_CLAIM_CLOSED->value,
            self::CLAIM_SUB_STATUS_CLAIM_PAID->value,
            self::CLAIM_SUB_STATUS_SETTLEMENT_IN_PROGRESS->value,

            // Motor/Car Specific Claim Sub Statuses
            self::CLAIM_SUB_STATUS_REPAIR_APPROVED_AND_WORK_IN_PROGRESS->value,
            self::CLAIM_SUB_STATUS_PARTS_ORDERED->value,
            self::CLAIM_SUB_STATUS_PARTS_ON_BACKORDER->value,
            self::CLAIM_SUB_STATUS_PARTS_DELAYED->value,
            self::CLAIM_SUB_STATUS_PARTS_ARRIVED_AND_WORK_IN_PROGRESS->value,
            self::CLAIM_SUB_STATUS_HIRE_CAR_REQUESTED->value,
            self::CLAIM_SUB_STATUS_HIRE_CAR_APPROVED->value,
            self::CLAIM_SUB_STATUS_HIRE_CAR_REFUND_IN_PROGRESS->value,
            self::CLAIM_SUB_STATUS_CAR_READY_FOR_COLLECTION->value,
            self::CLAIM_SUB_STATUS_REPAIR_COMPLETED->value,
            self::CLAIM_SUB_STATUS_REPAIR_COMPLETED_AND_CLAIM_SETTLED->value,
            self::CLAIM_SUB_STATUS_TOTAL_LOSS_APPROVED->value,
            self::CLAIM_SUB_STATUS_TOTAL_LOSS_OFFER_LETTER_SHARED->value,
            self::CLAIM_SUB_STATUS_TOTAL_LOSS_PAYMENT_IN_PROGRESS->value,
            self::CLAIM_SUB_STATUS_TOTAL_LOSS_PAID_AND_CLAIM_SETTLED->value,
            self::CLAIM_SUB_STATUS_CASH_LOSS_APPROVED->value,
            self::CLAIM_SUB_STATUS_CASH_LOSS_PAYMENT_IN_PROGRESS->value,
            self::CLAIM_SUB_STATUS_CASH_LOSS_PAID_AND_CLAIM_SETTLED->value,

            // Document Related Statuses
            self::CLAIM_SUB_STATUS_ADDITIONAL_DOCUMENTS_AWAITED->value,
            self::CLAIM_SUB_STATUS_DOCUMENTS_UPLOADED->value,
            self::CLAIM_SUB_STATUS_CLAIM_PENDING_FOR_ADDITIONAL_INFORMATION->value,

            // Health Specific Claim Sub Statuses
            self::CLAIM_SUB_STATUS_CLAIM_REPROCESSING->value,

            // Request Related Statuses (Health - Pending Approvals)
            self::CLAIM_SUB_STATUS_NEW_REQUEST->value,
            self::CLAIM_SUB_STATUS_UNDER_EVALUATION->value,
            self::CLAIM_SUB_STATUS_UNDER_RE_EVALUATION->value,
            self::CLAIM_SUB_STATUS_PARTIALLY_APPROVED->value,
            self::CLAIM_SUB_STATUS_REQUEST_DENIED->value,
            self::CLAIM_SUB_STATUS_REQUEST_APPROVED->value,

            // Question Related Statuses (Health - Ask a Question)
            self::CLAIM_SUB_STATUS_UNDER_REVIEW->value,
            self::CLAIM_SUB_STATUS_ANSWERED_AND_CLOSED->value,

            // Legacy Status Constants (for backward compatibility)
            self::CLAIM_SUB_STATUS_PAYMENT_INITIATED->value,
            self::CLAIM_SUB_STATUS_PAYMENT_COMPLETED->value,
        ];
    }

    /**
     * Get motor/car specific claim sub statuses
     */
    public static function getMotorClaimSubStatuses(): array
    {
        return [
            self::CLAIM_SUB_STATUS_NEW_CLAIM->value,
            self::CLAIM_SUB_STATUS_CLAIM_INITIATED->value,
            self::CLAIM_SUB_STATUS_CLAIM_REGISTERED_AWAITING_INSPECTION->value,
            self::CLAIM_SUB_STATUS_ESTIMATE_UNDER_REVIEW->value,
            self::CLAIM_SUB_STATUS_REPAIR_APPROVED_AND_WORK_IN_PROGRESS->value,
            self::CLAIM_SUB_STATUS_PARTS_ORDERED->value,
            self::CLAIM_SUB_STATUS_PARTS_ON_BACKORDER->value,
            self::CLAIM_SUB_STATUS_PARTS_DELAYED->value,
            self::CLAIM_SUB_STATUS_PARTS_ARRIVED_AND_WORK_IN_PROGRESS->value,
            self::CLAIM_SUB_STATUS_HIRE_CAR_REQUESTED->value,
            self::CLAIM_SUB_STATUS_HIRE_CAR_APPROVED->value,
            self::CLAIM_SUB_STATUS_HIRE_CAR_REFUND_IN_PROGRESS->value,
            self::CLAIM_SUB_STATUS_CAR_READY_FOR_COLLECTION->value,
            self::CLAIM_SUB_STATUS_REPAIR_COMPLETED_AND_CLAIM_SETTLED->value,
            self::CLAIM_SUB_STATUS_TOTAL_LOSS_APPROVED->value,
            self::CLAIM_SUB_STATUS_TOTAL_LOSS_OFFER_LETTER_SHARED->value,
            self::CLAIM_SUB_STATUS_TOTAL_LOSS_PAYMENT_IN_PROGRESS->value,
            self::CLAIM_SUB_STATUS_TOTAL_LOSS_PAID_AND_CLAIM_SETTLED->value,
            self::CLAIM_SUB_STATUS_CASH_LOSS_APPROVED->value,
            self::CLAIM_SUB_STATUS_CASH_LOSS_PAYMENT_IN_PROGRESS->value,
            self::CLAIM_SUB_STATUS_CASH_LOSS_PAID_AND_CLAIM_SETTLED->value,
            self::CLAIM_SUB_STATUS_CLAIM_WITHDRAWN->value,
            self::CLAIM_SUB_STATUS_CLAIM_DENIED->value,
            self::CLAIM_SUB_STATUS_ADDITIONAL_DOCUMENTS_AWAITED->value,
            self::CLAIM_SUB_STATUS_DOCUMENTS_UPLOADED->value,
        ];
    }

    /**
     * Get health reimbursement claim sub statuses
     */
    public static function getHealthReimbursementClaimSubStatuses(): array
    {
        return [
            self::CLAIM_SUB_STATUS_NEW_CLAIM->value,
            self::CLAIM_SUB_STATUS_CLAIM_INITIATED->value,
            self::CLAIM_SUB_STATUS_ADDITIONAL_DOCUMENTS_AWAITED->value,
            self::CLAIM_SUB_STATUS_CLAIM_REGISTERED->value,
            self::CLAIM_SUB_STATUS_CLAIM_UNDER_REVIEW->value,
            self::CLAIM_SUB_STATUS_CLAIM_APPROVED->value,
            self::CLAIM_SUB_STATUS_CLAIM_PARTIALLY_APPROVED->value,
            self::CLAIM_SUB_STATUS_CLAIM_PENDING_FOR_ADDITIONAL_INFORMATION->value,
            self::CLAIM_SUB_STATUS_CLAIM_REPROCESSING->value,
            self::CLAIM_SUB_STATUS_CLAIM_DENIED->value,
            self::CLAIM_SUB_STATUS_SETTLEMENT_IN_PROGRESS->value,
            self::CLAIM_SUB_STATUS_CLAIM_PAID->value,
            self::CLAIM_SUB_STATUS_CLAIM_CLOSED->value,
            self::CLAIM_SUB_STATUS_CLAIM_WITHDRAWN->value,
            self::CLAIM_SUB_STATUS_DOCUMENTS_UPLOADED->value,
        ];
    }

    /**
     * Get health pending approvals request sub statuses
     */
    public static function getHealthPendingApprovalsSubStatuses(): array
    {
        return [
            self::CLAIM_SUB_STATUS_NEW_REQUEST->value,
            self::CLAIM_SUB_STATUS_UNDER_EVALUATION->value,
            self::CLAIM_SUB_STATUS_ADDITIONAL_DOCUMENTS_AWAITED->value,
            self::CLAIM_SUB_STATUS_UNDER_RE_EVALUATION->value,
            self::CLAIM_SUB_STATUS_PARTIALLY_APPROVED->value,
            self::CLAIM_SUB_STATUS_REQUEST_DENIED->value,
            self::CLAIM_SUB_STATUS_REQUEST_APPROVED->value,
            self::CLAIM_SUB_STATUS_DOCUMENTS_UPLOADED->value,
        ];
    }

    /**
     * Get health ask question request sub statuses
     */
    public static function getHealthAskQuestionSubStatuses(): array
    {
        return [
            self::CLAIM_SUB_STATUS_NEW_REQUEST->value,
            self::CLAIM_SUB_STATUS_UNDER_REVIEW->value,
            self::CLAIM_SUB_STATUS_ANSWERED_AND_CLOSED->value,
        ];
    }

    public static function asArray(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $result[$case->name] = $case->value;
        }

        return $result;
    }

    /**
     * Override the label method to provide proper labels based on enum value.
     */
    public function label(): string
    {
        return Str::title($this->value);
    }

    /**
     * Get the enum with both value and label.
     */
    public function withLabel(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label(),
        ];
    }

    public static function withLabels(): array
    {
        return collect(self::cases())->map(function ($case) {
            return $case->withLabel();
        })->toArray();
    }
}
