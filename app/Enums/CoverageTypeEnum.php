<?php

namespace App\Enums;

enum CoverageTypeEnum: string
{
    case BUILDING_ONLY = 'Building only';
    case CONTENTS_ONLY = 'Contents only';
    case BUILDING_AND_CONTENTS = 'Building and Contents';
    case BUILDING_CONTENTS_PERSONAL_BELONGINGS = 'Building, Contents, and Personal belongings';
    case CONTENTS_PERSONAL_BELONGINGS = 'Contents and Personal belongings';

    // Database IDs for each coverage type
    public const BUILDING_ONLY_ID = 506;
    public const CONTENTS_ONLY_ID = 507;
    public const BUILDING_AND_CONTENTS_ID = 508;
    public const BUILDING_CONTENTS_PERSONAL_BELONGINGS_ID = 509;
    public const CONTENTS_PERSONAL_BELONGINGS_ID = 510;

    /**
     * Get all cases as an array of [name => id]
     * This provides a structure where the keys are the enum case names and the values are the IDs
     */
    public static function nameToIdArray(): array
    {
        return [
            'BUILDING_ONLY' => self::BUILDING_ONLY_ID,
            'CONTENTS_ONLY' => self::CONTENTS_ONLY_ID,
            'BUILDING_AND_CONTENTS' => self::BUILDING_AND_CONTENTS_ID,
            'BUILDING_CONTENTS_PERSONAL_BELONGINGS' => self::BUILDING_CONTENTS_PERSONAL_BELONGINGS_ID,
            'CONTENTS_PERSONAL_BELONGINGS' => self::CONTENTS_PERSONAL_BELONGINGS_ID,
        ];
    }
}
