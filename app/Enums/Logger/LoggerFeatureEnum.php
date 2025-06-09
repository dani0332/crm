<?php

namespace App\Enums\Logger;

enum LoggerFeatureEnum: string
{
    case ALLOCATION = 'allocation';
    case ALLOCATION_AUDIT = 'allocation-audit';
    case FTC_EMAIL = 'ftc-email';
    case TRAVEL_RENEWALS = 'travel-renewals';
    case AML_SCREENING = 'aml-screening';
    case POLICY_AUTOMATION = 'policy-automation';
    case SAGE_POLICY_BOOKING = 'sage-policy-booking';
    case SAGE_ENDORSEMENT_BOOKING = 'sage-endorsement-booking';
    case SAGE_POST_PREPAYMENT = 'sage-post-prepayment';
}
