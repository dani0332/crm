<?php

declare(strict_types=1);

namespace App\Enums;

enum DeviceFailureTypeEnum: string
{
    case BOOK_POLICY = 'book_policy';
    case AUTO_CAPTURE_PAYMENT = 'auto_capture_payment';
    case ISSUE_POLICY = 'issue_policy';
    case GET_AND_UPLOAD_DOCUMENTS = 'get_and_upload_documents';

    /**
     * Get the trigger point text for email as per FRD
     */
    public function getTriggerPointText(): string
    {
        return match ($this) {
            self::BOOK_POLICY => 'Retrieval of Required Booking Details via API',
            self::AUTO_CAPTURE_PAYMENT => 'Auto Capture Payment',
            self::ISSUE_POLICY => 'Retrieval of Required Policy Details via API / Policy Issuance API',
            self::GET_AND_UPLOAD_DOCUMENTS => 'Upload of Required Documents via API',
        };
    }

    /**
     * Get human-readable failure type name
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::BOOK_POLICY => 'Booking Details API',
            self::AUTO_CAPTURE_PAYMENT => 'Auto Capture Payment',
            self::ISSUE_POLICY => 'Policy Details API',
            self::GET_AND_UPLOAD_DOCUMENTS => 'Upload Documents API',
        };
    }
}
