<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class Kyc extends Enum
{
    const PENDING = 'Pending';
    const COMPLETE = 'Complete';
    const PROFESSION_TWO_RATING = ['air-traffic-controller', 'businessman', 'businesswoman', 'military-service-person', 'police-officer', 'pro-unspecified'];
    const PROFESSION_THREE_RATING = ['accountant', 'actor-actress', 'auditor', 'lawyer', 'politician'];
    const COUNTRY_NATIONALITY_FOUR_RATING = ['north korea', 'iran'];
    const PAYMENT_MODE_TWO_RATING = ['chq', 'pdc', 'pp', 'mp', 'ca', 'ppr', 'in_pl'];
    const PAYMENT_MODE_THREE_RATING = ['csh', 'third party payment'];
    const PAYMENT_MODE_ONE_RATING = ['bt', 'cc', 'ip'];
    const MODE_OF_CONTACT_THREE_RATING = ['non_face_to_face'];
    const MODE_OF_DELIVERY_THREE_RATING = ['authorised third party', 'mod-delivery-unkown', 'mod-delivery-atp'];
    const RESIDENT_STATUS_THREE_RATING = ['nonuaeresident'];
    const TENURE_TWO_RATING = ['2'];
    const TENURE_THREE_RATING = ['1'];
    const EMPLOYMENT_SECTOR_TWO_RATING = ['private', 'unspecifiedempsec', 'emp-sector-unspecified'];
    const EMPLOYMENT_SECTOR_THREE_RATING = ['freezone'];
    const TRANSACTION_VOLUME_TWO_RATING = ['3', '4'];
    const TRANSACTION_VOLUME_THREE_RATING = ['5'];
    const DOCUMENT_TYPE_EMIRATES = 'emiratesId';
    const MODE_OF_DELIVERY = [
        'mod-delivery-car' => 'Company Authorised Representative',
        'mod-delivery-atp' => 'Authorised Third party',
        'mod-delivery-unkown' => 'Unknown',
        'mod-delivery-pse' => 'Policy sent via email',
        'mod-delivery-psc' => 'Policy sent via courier',
        'mod-delivery-cco' => 'Collected by customer from office',
    ];
    const TRANSACTION_VOLUME = [
        'less-than-3-in-month' => 'Less than 3 transactions in a month',
        '4-7-in-month' => '4 to 7 transactions in a month',
        'more-than-8-in-month' => 'More than 8 transactions in a month',
    ];
    const ENTITY_TRANSACTION_VOLUME_TWO_RATING = ['4-7-in-month'];
    const ENTITY_TRANSACTION_VOLUME_THREE_RATING = ['more-than-8-in-month'];
    const TRANSACTION_ACTIVITIES = [
        'less_expected_annual_activity' => 'Less than expected Annual Activity',
        'near_expected_annual_activity' => 'Near to expected Annual Activity',
        'more_expected_annual_activity' => 'More than expected Annual Activity',
    ];
    const TRANSACTION_ACTIVITIES_TWO_RATING = ['near_expected_annual_activity'];
    const TRANSACTION_ACTIVITIES_THREE_RATING = ['more_expected_annual_activity'];
    const ENTITY_LEGAL_STRUCTURE_TWO_RATING = ['privatejointstockcompany'];
    const ENTITY_LEGAL_STRUCTURE_THREE_RATING = ['limitedliabilitycompany', 'establishment', 'branchofforeigncompany'];
    const ENTITY_INDUSTRY_TYPE_ONE_RATING = ['legalservices', 'consultancy', 'serviceprovider'];
    const ENTITY_INDUSTRY_TYPE_TWO_RATING = ['brokers', 'construction', 'foodbeverages', 'others'];
    const TRANSACTION_PATTERN = [
        'count_pattern_changes' => 'Yes - Count Pattern Changes',
        'behaviour_changes' => 'Yes - Behaviour Changes',
        'business_model_changes' => 'Yes - Business Model Changes',
        'no_changes' => 'No Changes',
        'not_applicable' => 'Not Applicable',
    ];
    const PREMIUM_TENURE = [
        'single_premium' => 'Single Premium',
        'quarter_premium' => 'Quarterly/Semi-Annual',
        'monthly' => 'Monthly',
    ];
    const PREMIUM_TENURE_TWO_RATING = ['quarter_premium'];
    const PREMIUM_TENURE_THREE_RATING = ['single_premium'];
    const TRANSACTION_PATTERN_THREE_RATING = ['count_pattern_changes', 'behaviour_changes', 'business_model_changes'];
    const TRANSACTION_PATTERN_ZERO_RATING = ['not_applicable'];
    const ENTITY_MODE_OF_DELIVERY_THREE_RATING = ['mod-delivery-atp', 'mod-delivery-unkown'];
    const ENTITY_MODE_OF_CONTACT_THREE_RATING = ['non-face-to-face'];
}
