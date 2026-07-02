<?php

namespace App\Enums;

enum HealthPlanRateSheetStatusEnum: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';
    case ACTIVE = 'active';
    case SCHEDULED = 'scheduled';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
