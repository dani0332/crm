<?php

namespace App\Enums;

/**
 * Claims Enum
 *
 * Consolidates all claim-related constants, statuses, types, and configuration values
 * that were previously scattered across controllers, services, and models.
 */
enum ClaimsEnum: string
{
    use Enumable;

    case CLAIM_TYPES_KEY = 'claim-types';
    case CLAIM_REQUEST_TYPES_KEY = 'claim-request-types';
    case CLAIM_SERVICE_TYPES_KEY = 'claim-service-types';
    case CLAIM_STATUS_ACCESS_TYPES_KEY = 'claim-status-access-types'; 

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

    // Claim Request Access Type Codes
    case CLAIM_REQUEST_ACCESS_TYPE_SYSTEM_GENERATED_CODE = 'system-generated';
    case CLAIM_REQUEST_ACCESS_TYPE_MANUAL_CODE = 'manual';

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
     * Get all claim service type codes
     */
    public static function getClaimServiceTypeCodes(): array
    {
        return [
            self::CLAIM_SERVICE_TYPE_IN_PATIENT_REQUEST_CODE->value,
            self::CLAIM_SERVICE_TYPE_OUT_PATIENT_REQUEST_CODE->value,
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

    public static function asArray(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $result[$case->name] = [
                'name' => $case->name,
                'value' => $case->value,
            ];
        }

        return $result;
    }
 
}
