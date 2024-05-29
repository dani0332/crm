<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class DisplayByEnum extends Enum
{
    const ADVISOR_NAME = 'advisor_name';
    const SUBTEAM = 'sub_team';
    const LEADSOURCE = 'lead_source';
    const EXTERNAL_LEADSOURCE = 'external_lead_source';
    const TIERS = 'tiers';
    const NATIONALITY = 'nationality';
    const TEAM = 'team';
}
