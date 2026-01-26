<?php

declare(strict_types=1);

namespace App\Services\HealthTeamRouting;

use App\Enums\ApplicationStorageEnums;

trait HealthTeamRoutable
{
    private function isHealthTeamRoutingEnabled(): bool
    {
        return getAppStorageValueByKey(ApplicationStorageEnums::HEALTH_TEAM_ROUTING_ENABLED, useCache: true) == ApplicationStorageEnums::ACTIVE;
    }
}
