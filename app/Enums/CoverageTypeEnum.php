<?php

namespace App\Enums;

enum CoverageTypeEnum: string
{
    case BUILDING_ONLY = 'Building only';
    case CONTENTS_ONLY = 'Contents only';
    case BUILDING_AND_CONTENTS = 'Building and Contents';
    case BUILDING_CONTENTS_PERSONAL_BELONGINGS = 'Building, Contents, and Personal belongings';
    case CONTENTS_PERSONAL_BELONGINGS = 'Contents and Personal belongings';
}
