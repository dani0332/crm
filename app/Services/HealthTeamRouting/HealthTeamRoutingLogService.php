<?php

namespace App\Services\HealthTeamRouting;

use App\Enums\HealthRoutingLogTypeEnum;
use App\Models\HealthRoutingLog;
use App\Services\Logger\LoggerService;

class HealthTeamRoutingLogService
{
    public function log(HealthRoutingLogTypeEnum $type, array $logData, ?int $quoteRequestId, ?string $uuid): void
    {
        try {
            HealthRoutingLog::create([
                'quote_request_id' => $quoteRequestId,
                'uuid' => $uuid,
                'type' => $type->value,
                'log_data' => $logData, // Model casts to array automatically
            ]);
        } catch (\Exception $e) {
            LoggerService::error('HealthTeamRoutingLogService: log function error', ['error' => $e->getMessage()]);
        }
    }
}
