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
    const BOOKING_PENDING_STATUS = 'booking_pending';
    const BOOKING_PROCESSING_STATUS = 'booking_processing';

    // Policy Issuance Automation Statuses IDs
    const PIA_POLICY_AUTOMATION_STATUS_YES_ID = 1;
    const PIA_POLICY_AUTOMATION_STATUS_NO_ID = 2;

    // Policy Issuance Automation Statuses Messages
    const PIA_POLICY_AUTOMATION_STATUS_YES = 'Yes';
    const PIA_POLICY_AUTOMATION_STATUS_NO = 'No';

    // Policy Issuance Insurer API Statuses for all automations
    // Policy Issuance Automation Insurer Statuses IDs
    const PIA_AUTO_CAPTURE_FAILED_STATUS_ID = 1;
    const PIA_UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID = 2;
    const PIA_POLICY_ISSUANCE_API_FAILED_STATUS_ID = 3;
    const PIA_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID = 4;
    const PIA_OCR_PROCESSING_API_FAILED_STATUS_ID = 5;
    const PIA_BOOK_POLICY_API_FAILED_STATUS_ID = 6;
    const PIA_PREVIOUS_POLICY_EXPIRED_STATUS_ID = 99;

    // Policy Issuance Automation Insurer Statuses Messages
    const PIA_AUTO_CAPTURE_FAILED = 'Auto Capture Failed';
    const PIA_UPLOAD_POLICY_DOCUMENTS_API_FAILED = 'Insurer Document Upload Failed';
    const PIA_POLICY_ISSUANCE_API_FAILED = 'Policy Creation Failed';
    const PIA_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED = 'Policy Document Retrieval Failed';
    const PIA_OCR_PROCESSING_API_FAILED = 'OCR Processing Failed';
    const PIA_BOOK_POLICY_API_FAILED = 'Send and Book Policy Failed';
    const PIA_PREVIOUS_POLICY_EXPIRED = 'Previous policy has expired';

    // Policy Issuance Automation Insurer Statuses Action Messages
    const PIA_AUTO_CAPTURE_ACTION_MESSAGE = 'Auto Capture Payment';
    const PIA_UPLOAD_POLICY_DOCUMENTS_API_ACTION_MESSAGE = 'Insurer Document Upload via API';
    const PIA_POLICY_ISSUANCE_API_ACTION_MESSAGE = 'Policy Issuance via API';
    const PIA_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_ACTION_MESSAGE = 'Get and Upload Policy Documents to IMCRM via API';
    const PIA_OCR_PROCESSING_API_ACTION_MESSAGE = 'OCR Processing via API';
    const PIA_BOOK_POLICY_API_ACTION_MESSAGE = 'Book Policy via API';
    const PIA_PREVIOUS_POLICY_EXPIRED_ACTION_MESSAGE = 'Previous policy has expired';

    // Policy Issuance AutomationRTA statuses
    const PIA_RTA_UPLOAD_STATUS_PENDING = '0';
    const PIA_RTA_UPLOAD_STATUS_DONE = '1';

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

    /* LIVA AML API Statuses */

    const LIVA_AML_ACTIVE = 1;
    const LIVA_AML_ACCEPTED = 23;

    /* Alliance Travel Steps */
    public static function getPolicyIssuanceSteps($insurerCode, $quoteType)
    {
        return match (ucfirst($quoteType)) {
            QuoteTypes::TRAVEL->value => match ($insurerCode) {
                InsuranceProviderEnum::ALNC->value => self::getTravelAlliancePolicyIssuanceSteps(),
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
