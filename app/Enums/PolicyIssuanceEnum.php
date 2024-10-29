<?php

declare(strict_types=1);

namespace App\Enums;

use App\Services\PolicyIssuanceAutomation\Travel\AllianceInsuranceService;
use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class PolicyIssuanceEnum extends Enum
{
    const PENDING_STATUS = 'pending';
    const PROCESSING_STATUS = 'processing';
    const TIMEOUT_STATUS = 'timeout';
    const COMPLETED_STATUS = 'completed';
    const FAILED_STATUS = 'failed';


    /* Alliance Travel Steps*/
    const ALLIANCE_TRAVEL_ISSUE_POLICY = 'IssuePolicy';
    const ALLIANCE_TRAVEL_PURCHASE_POLICY = 'PurchasePolicy';
    const ALLIANCE_TRAVEL_UPLOAD_POLICY_DOCUMENTS = 'UploadPolicyDocuments';
    const ALLIANCE_TRAVEL_FILL_POLICY_BOOKING_DETAILS = 'FillPolicyBookingDetails';
    const ALLIANCE_TRAVEL_BOOK_POLICY = 'BookPolicy';

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
}
