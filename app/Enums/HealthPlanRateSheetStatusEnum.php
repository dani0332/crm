<?php

namespace App\Enums;

enum HealthPlanRateSheetStatusEnum: string
{
    case DRAFT = 'Draft';
    case PUBLISHED = 'Published';
    case ARCHIVED = 'Archived';
    case ACTIVE = 'Active';
}
