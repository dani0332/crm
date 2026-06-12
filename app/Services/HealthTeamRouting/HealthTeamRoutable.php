<?php

declare(strict_types=1);

namespace App\Services\HealthTeamRouting;

use App\Enums\ApplicationStorageEnums;
use App\Enums\TeamCategoryEnum;
use App\Enums\TeamNameEnum;
use App\Models\Team;

trait HealthTeamRoutable
{
    private function isHealthTeamRoutingEnabled(): bool
    {
        return getAppStorageValueByKey(ApplicationStorageEnums::HEALTH_TEAM_ROUTING_ENABLED, useCache: true) == ApplicationStorageEnums::ACTIVE;
    }

    private function fetchTeamByPriceAndCategory($price, TeamCategoryEnum $category): ?Team
    {
        if ($price === null) {
            return null;
        }

        return Team::where('allocation_threshold_enabled', true)
            ->where('min_price', '<=', $price)
            ->where('max_price', '>=', $price)
            ->where('category', $category->value)
            ->active()
            ->first();
    }

    private function getGbpTeamMinPrice()
    {
        $gbpTeam = Team::where('allocation_threshold_enabled', true)
            ->where('name', TeamNameEnum::GBP)
            ->active()
            ->first();

        return $gbpTeam?->min_price;
    }
}
