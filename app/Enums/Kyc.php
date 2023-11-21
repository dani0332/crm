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
    const PROFESSION_TWO_RATING = ['air-traffic-controll', 'businessman', 'businesswoman', 'military-service-per', 'police-officer'];
    const PROFESSION_THREE_RATING = ['accountant', 'actor-actress', 'auditor', 'lawyer', 'politician'];
    const COUNTRY_NATIONALITY_FOUR_RATING = ['north korea', 'iran'];
    const PAYMENT_MODE_TWO_RATING = ['CHQ'];
    const PAYMENT_MODE_THREE_RATING = ['CSH', 'third party payment'];
    const MODE_OF_CONTACT_THREE_RATING = ['non_face_to_face', 'email', 'phone', 'phoneandemail'];
    const MODE_OF_DELIVERY_THREE_RATING = ['authorised third party', 'unknown', 'email'];
    const RESIDENT_STATUS_THREE_RATING = ['nonuaeresident'];
    const TENURE_TWO_RATING = ['2'];
    const TENURE_THREE_RATING = ['1'];
    const EMPLOYMENT_SECTOR_TWO_RATING = ['private'];
    const EMPLOYMENT_SECTOR_THREE_RATING = ['freezone'];
    const TRANSACTION_VOLUME_TWO_RATING = ['3', '4'];
    const TRANSACTION_VOLUME_THREE_RATING = ['5'];
    const MODE_OF_DELIVERY = [
        'mod-delivery-car'=>'Company Authorised Representative',
        'mod-delivery-atp'=>'Authorised Third party',
        'mod-delivery-unkown'=>'Unknown',
        'mod-delivery-pse'=>'Policy sent via email',
        'mod-delivery-psc'=>'Policy sent via courier',
        'mod-delivery-cco'=>'Collected by customer from office'
    ];
}
