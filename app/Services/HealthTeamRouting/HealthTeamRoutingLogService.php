<?php

namespace App\Services\HealthTeamRouting;

use App\Enums\HealthRoutingLogTypeEnum;
use App\Enums\TeamCategoryEnum;
use App\Models\HealthRoutingLog;
use App\Services\Logger\LoggerService;

class HealthTeamRoutingLogService
{
    public static function log(
        HealthRoutingLogTypeEnum $type,
        array $logData,
        ?int $quoteRequestId = null,
        ?string $uuid = null,
        ?TeamCategoryEnum $teamCategory = null): void
    {
        try {
            HealthRoutingLog::create([
                'quote_request_id' => $quoteRequestId,
                'uuid' => $uuid,
                'type' => $type->value,
                'team_category' => $teamCategory?->value,
                'log_data' => json_encode($logData),
            ]);
        } catch (\Exception $e) {
            LoggerService::error('HealthTeamRoutingLogService: log function error', ['error' => $e->getMessage()]);
        }
    }
}
