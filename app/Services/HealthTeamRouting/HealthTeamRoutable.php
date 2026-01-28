<?php

declare(strict_types=1);

namespace App\Services\HealthTeamRouting;

use App\Enums\ApplicationStorageEnums;
use App\Enums\HealthRoutingLogTypeEnum;
use App\Enums\TeamCategoryEnum;
use App\Enums\TeamNameEnum;
use App\Models\HealthQuote;
use App\Models\Team;
use App\Services\Logger\LoggerService;

trait HealthTeamRoutable
{
    private function isHealthTeamRoutingEnabled(): bool
    {
        return getAppStorageValueByKey(ApplicationStorageEnums::HEALTH_TEAM_ROUTING_ENABLED, useCache: true) == ApplicationStorageEnums::ACTIVE;
    }

    private function logStep(string $message, string $step, array $flags, HealthQuote $lead): void
    {
        $this->healthTeamRoutingLogService->log(
            HealthRoutingLogTypeEnum::ROUTING,
            array_merge(
                ['message' => $message, 'step' => $step],
                $flags
            ),
            $lead->id,
            $lead->uuid
        );
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

    private function logGbpCheckResult($gbpMinPrice, $priceStartingFrom, string $routingType = 'routing'): void
    {
        if ($gbpMinPrice === null) {
            LoggerService::info("GBP team not found for AUH category, proceeding with regular {$routingType} routing");
        } elseif ($priceStartingFrom < $gbpMinPrice) {
            LoggerService::info("Price starting from ({$priceStartingFrom}) is less than GBP min price ({$gbpMinPrice}), proceeding with regular {$routingType} routing");
        }
    }
}
