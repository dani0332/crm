<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class LeadSourceEnum extends Enum
{
    const REFERRAL = 'REFERRAL';
    const RENEWAL_UPLOAD = 'Renewal_upload';
    const TPL_RENEWALS = 'TPL_RENEWALS';
    const IMCRM = 'IMCRM';
    const REVIVAL = 'REVIVAL';
    const INSLY = 'Insly';
    const REVIVAL_REPLIED = 'REVIVAL_REPLIED';
}
