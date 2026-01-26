<?php

namespace App\Services\HealthTeamRouting;

use App\Enums\HealthRoutingLogTypeEnum;
use App\Models\HealthRoutingLog;
use App\Services\Logger\LoggerService;

class HealthTeamRoutingLogService
{
    public static function log(HealthRoutingLogTypeEnum $type, array $logData, ?int $quoteRequestId, ?string $uuid): void
    {
        try {
            HealthRoutingLog::create([
                'quote_request_id' => $quoteRequestId,
                'uuid' => $uuid,
                'type' => $type->value,
                'log_data' => json_encode($logData),
            ]);
        } catch (\Exception $e) {
            LoggerService::error('HealthTeamRoutingLogService: log function error', ['error' => $e->getMessage()]);
        }
    }
}
