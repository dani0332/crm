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
            self::CLAIM_TYPE_OWN_DAMAGE_CLAIM_CODE,
            self::CLAIM_TYPE_RECOVERABLE_CLAIM_CODE,
            self::CLAIM_TYPE_UNKNOWN_DAMAGE_CLAIM_CODE,
            self::CLAIM_TYPE_WATER_DAMAGE_CODE,
            self::CLAIM_TYPE_THEFT_CODE,
            self::CLAIM_TYPE_FIRE_ARSON_CODE,
            self::CLAIM_TYPE_WINDSCREEN_ONLY_CODE,
        ];
    }

    /**
     * Get all claim request type codes
     */
    public static function getClaimRequestTypeCodes(): array
    {
        return [
            self::CLAIM_REQUEST_TYPE_REIMBURSEMENT_CODE,
            self::CLAIM_REQUEST_TYPE_PENDING_APPROVALS_CODE,
            self::CLAIM_REQUEST_TYPE_ASK_A_QUESTION_CODE,
        ];
    }

    /**
     * Get all claim service type codes
     */
    public static function getClaimServiceTypeCodes(): array
    {
        return [
            self::CLAIM_SERVICE_TYPE_IN_PATIENT_REQUEST_CODE,
            self::CLAIM_SERVICE_TYPE_OUT_PATIENT_REQUEST_CODE,
        ];
    }

    /**
     * Get all claim request access type codes
     */
    public static function getClaimRequestAccessTypeCodes(): array
    {
        return [
            self::CLAIM_REQUEST_ACCESS_TYPE_SYSTEM_GENERATED_CODE,
            self::CLAIM_REQUEST_ACCESS_TYPE_MANUAL_CODE,
        ];
    }

    /**
     * Check if the given value is a valid claim type code
     */
    public static function isValidClaimTypeCode(string $code): bool
    {
        return in_array($code, array_column(self::getClaimTypeCodes(), 'value'));
    }

    /**
     * Check if the given value is a valid claim request type code
     */
    public static function isValidClaimRequestTypeCode(string $code): bool
    {
        return in_array($code, array_column(self::getClaimRequestTypeCodes(), 'value'));
    }

    /**
     * Check if the given value is a valid claim service type code
     */
    public static function isValidClaimServiceTypeCode(string $code): bool
    {
        return in_array($code, array_column(self::getClaimServiceTypeCodes(), 'value'));
    }

    /**
     * Check if the given value is a valid claim request access type code
     */
    public static function isValidClaimRequestAccessTypeCode(string $code): bool
    {
        return in_array($code, array_column(self::getClaimRequestAccessTypeCodes(), 'value'));
    }
}
