<?php

namespace App\Services\HealthTeamRouting;

use App\Enums\HealthRoutingLogTypeEnum;
use App\Enums\HealthRoutingSourceEnum;
use App\Enums\TeamCategoryEnum;
use App\Enums\UserNameEnum;
use App\Models\HealthRoutingLog;
use App\Models\User;
use App\Services\Logger\LoggerService;
use Illuminate\Support\Facades\Auth;

class HealthTeamRoutingLogService
{
    public static function log(
        HealthRoutingLogTypeEnum $type,
        array $logData,
        ?int $quoteRequestId = null,
        ?string $uuid = null,
        ?TeamCategoryEnum $teamCategory = null,
        ?HealthRoutingSourceEnum $source = null): void
    {
        try {
            $loggedBy = Auth::check() ? Auth::id() : User::where('name', UserNameEnum::System)->value('id');

            HealthRoutingLog::create([
                'quote_request_id' => $quoteRequestId,
                'uuid' => $uuid,
                'type' => $type->value,
                'team_category' => $teamCategory,
                'log_data' => $logData,
                'logged_by' => $loggedBy,
                'source' => $source,
            ]);
        } catch (\Exception $e) {
            LoggerService::error('HealthTeamRoutingLogService: log function error', ['error' => $e->getMessage()]);
        }
    }
}
