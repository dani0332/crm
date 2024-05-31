<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class TeamTypeEnum extends Enum
{
    public const PRODUCT = 1;
    public const TEAM = 2;
    public const SUB_TEAM = 3;
    public const PRODUCT_STR = 'Product';
    public const TEAM_STR = 'Team';
    public const SUBTEAM_STR = 'Subteam';
    public const CAR_PARENT_TEAM_ID = 2;
    public const HEALTH_PARENT_TEAM_ID = 7;
}
