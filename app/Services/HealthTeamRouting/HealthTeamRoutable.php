<?php

declare(strict_types=1);

namespace App\Services\HealthTeamRouting;

use App\Enums\ApplicationStorageEnums;
use App\Enums\HealthRoutingLogTypeEnum;
use App\Models\HealthQuote;

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
}
