<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class PolicyIssuanceEnum extends Enum
{
    // Advisor email to be used to assign advisor to leads which booked automatically using policy issuance automations
    const API_POLICY_ISSUANCE_AUTOMATION_USER_EMAIL = 'happiness@support.insurancemarket.ae';
    const API_POLICY_ISSUANCE_AUTOMATION_USER_LABEL = 'Auto Issued';
    const PENDING_STATUS = 'pending';
    const PROCESSING_STATUS = 'processing';
    const TIMEOUT_STATUS = 'timeout';
    const COMPLETED_STATUS = 'completed';
    const FAILED_STATUS = 'failed';
    const SUCCESS_STATUS = 'success';

    /* Insurer API Generic Status */

    const POLICY_ISSUANCE_API_STATUS_YES_ID = 1;
    const POLICY_ISSUANCE_API_STATUS_YES = 'Yes';
    const POLICY_ISSUANCE_API_STATUS_NO_ID = 2;
    const POLICY_ISSUANCE_API_STATUS_NO = 'No';
    const AUTO_CAPTURE_FAILED_STATUS_ID = 1;
    const AUTO_CAPTURE_FAILED = 'Auto Capture Failed';
    const AUTO_CAPTURE_ACTION_MESSAGE = 'Auto Capture Payment';
    const POLICY_DETAIL_API_FAILED_STATUS_ID = 2;
    const POLICY_DETAIL_API_FAILED = 'Policy Details API Failed';
    const POLICY_DETAIL_API_ACTION_MESSAGE = 'Retrieval of Required Policy Details via API / Policy Issuance API';
    const UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID = 3;
    const UPLOAD_POLICY_DOCUMENTS_API_FAILED = 'Document Upload API Failed';
    const UPLOAD_POLICY_DOCUMENTS_API_ACTION_MESSAGE = 'Document Upload via API';
    const BOOKING_DETAILS_API_FAILED_STATUS_ID = 4;
    const BOOKING_DETAILS_API_FAILED = 'Booking Details API Failed';
    const BOOKING_DETAILS_API_ACTION_MESSAGE = 'Retrieval of Required Booking Details via API';

    /* Insurer API Generic Status */

    /* Alliance Travel Steps */

    const ALLIANCE_TRAVEL_ISSUE_POLICY = 'IssuePolicy';
    const ALLIANCE_TRAVEL_PURCHASE_POLICY = 'PurchasePolicy';
    const ALLIANCE_TRAVEL_UPLOAD_POLICY_DOCUMENTS = 'UploadPolicyDocuments';
    const ALLIANCE_TRAVEL_FILL_POLICY_BOOKING_DETAILS = 'FillPolicyBookingDetails';
    const ALLIANCE_TRAVEL_BOOK_POLICY = 'BookPolicy';

    /* Alliance Travel Steps */
    public static function getPolicyIssuanceSteps($insurerCode, $quoteType)
    {
        return match (ucfirst($quoteType)) {
            QuoteTypes::TRAVEL->value => match ($insurerCode) {
                InsuranceProvidersEnum::ALNC => self::getTravelAlliancePolicyIssuanceSteps(),
                default => null,
            },
            default => null,
        };
    }
    public static function getTravelAlliancePolicyIssuanceSteps()
    {
        return [
            self::ALLIANCE_TRAVEL_ISSUE_POLICY,
            self::ALLIANCE_TRAVEL_PURCHASE_POLICY,
            self::ALLIANCE_TRAVEL_UPLOAD_POLICY_DOCUMENTS,
            self::ALLIANCE_TRAVEL_FILL_POLICY_BOOKING_DETAILS,
            self::ALLIANCE_TRAVEL_BOOK_POLICY,
        ];
    }
    public static function getInsurerAPIStatuses($status = null)
    {
        $statuses = [
            self::AUTO_CAPTURE_FAILED_STATUS_ID => self::AUTO_CAPTURE_FAILED,
            self::POLICY_DETAIL_API_FAILED_STATUS_ID => self::POLICY_DETAIL_API_FAILED,
            self::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID => self::UPLOAD_POLICY_DOCUMENTS_API_FAILED,
            self::BOOKING_DETAILS_API_FAILED_STATUS_ID => self::BOOKING_DETAILS_API_FAILED,
        ];

        return $status ? $statuses[$status] : $statuses;
    }

    public static function getInsurerAPIEmailActionMessage($status = null)
    {
        $statuses = [
            self::AUTO_CAPTURE_FAILED_STATUS_ID => self::AUTO_CAPTURE_ACTION_MESSAGE,
            self::POLICY_DETAIL_API_FAILED_STATUS_ID => self::POLICY_DETAIL_API_ACTION_MESSAGE,
            self::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID => self::UPLOAD_POLICY_DOCUMENTS_API_ACTION_MESSAGE,
            self::BOOKING_DETAILS_API_FAILED_STATUS_ID => self::BOOKING_DETAILS_API_ACTION_MESSAGE,
        ];

        return $status ? $statuses[$status] : '';
    }

    public static function getAPIIssuanceStatuses($status = null, bool $getAll = false)
    {
        $statuses = [
            self::POLICY_ISSUANCE_API_STATUS_YES_ID => self::POLICY_ISSUANCE_API_STATUS_YES,
            self::POLICY_ISSUANCE_API_STATUS_NO_ID => self::POLICY_ISSUANCE_API_STATUS_NO,
        ];

        if ($getAll) {
            $statuses['blank'] = 'Blank';

            return $statuses;
        }

        return $status ? $statuses[$status] : '';
    }
}
