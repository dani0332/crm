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
    const PROFESSION_TWO_RATING = ['air traffic controller', 'businessman', 'businesswoman', 'military service person', 'police officer'];
    const PROFESSION_THREE_RATING = ['accountant', 'actor / actress', 'auditor', 'lawyer', 'politician'];
    const COUNTRY_NATIONALITY_FOUR_RATING = ['north korea', 'iran, islamic republic of'];
    const PAYMENT_MODE_TWO_RATING = ['CHQ'];
    const PAYMENT_MODE_THREE_RATING = ['CSH', 'third party payment'];
    const MODE_OF_CONTACT_THREE_RATING = ['non face to face', 'email', 'phone', 'email and phone'];
    const MODE_OF_DELIVERY_THREE_RATING = ['authorised third party', 'unknown', 'policy sent via email'];
    const RESIDENT_STATUS_THREE_RATING = ['non resident'];
    const TENURE_TWO_RATING = ['2'];
    const TENURE_THREE_RATING = ['1'];
    const EMPLOYMENT_SECTOR_TWO_RATING = ['private sector'];
    const EMPLOYMENT_SECTOR_THREE_RATING = ['freezone sector'];
    const TRANSACTION_VOLUME_TWO_RATING = ['3', '4'];
    const TRANSACTION_VOLUME_THREE_RATING = ['5'];
}
