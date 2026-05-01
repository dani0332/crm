<?php

namespace App\Pipes\Allocation\Health;

use App\Enums\HealthRoutingLogTypeEnum;
use App\Enums\HealthRoutingSourceEnum;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\HealthTeamRouting\HealthTeamRoutable;
use App\Services\HealthTeamRouting\HealthTeamRoutingLogService;
use App\Services\HealthTeamRouting\HealthTeamRoutingService;
use App\Services\Logger\LoggerService;
use Closure;

class AssignTeamPipe extends BaseAllocationPipe
{
    use HealthTeamRoutable;

    private HealthRoutingSourceEnum $source;
    /**
     * Handle the incoming request.
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->source = $request->getSource();
        $this->setRequest($request);
        $logService = app(HealthTeamRoutingLogService::class);

        // Skip team assignment if already assigned (Duplicate lead case)
        if ($this->lead->health_team_type) {
            LoggerService::info('Lead already has a team assigned (probably reassignment case), skipping team assignment', ['source' => $this->source, 'uuid' => $this->lead->uuid]);

            return $next($request);
        }

        // Check if lead source is applicable
        if (! $this->lead->isSourceApplicable()) {
            // Terminate
            LoggerService::info('Lead source is not applicable, health team routing is not applicable', [
                'source' => $this->source,
                'lead_source' => $this->lead->source,
                'uuid' => $this->lead->uuid]);
            $logService->log(
                HealthRoutingLogTypeEnum::ROUTING,
                [
                    'message' => 'Lead source is not applicable, health team routing is not applicable',
                    'step' => 'ecom source check',
                    'lead_source' => $this->lead->source,
                ],
                $this->lead->id,
                $this->lead->uuid,
                null,
                $this->source
            );

            return $next($request);
        }

        // Check if routing enabled
        if (! $this->isHealthTeamRoutingEnabled()) {
            LoggerService::info('Health team routing is not enabled', ['source' => $this->source, 'uuid' => $this->lead->uuid]);
            $logService->log(
                HealthRoutingLogTypeEnum::ROUTING,
                [
                    'message' => 'Health team routing is not enabled',
                    'step' => 'routing enabled check',
                    'is_routing_enabled' => false,
                    'source' => $this->source,
                ],
                $this->lead->id,
                $this->lead->uuid,
                null,
                $this->source
            );

            return $next($request);
        }

        LoggerService::info('------ Health team routing starts ------', ['source' => $this->source, 'uuid' => $this->lead->uuid]);
        $isAUHLead = $this->lead->isAUHLead(false);

        // AUH path
        if ($isAUHLead) {
            LoggerService::info('Lead is AUH lead, triggering AUH tier routing', [
                'emirates_of_visa' => $this->lead->emirate?->text,
                'source' => $this->source,
                'uuid' => $this->lead->uuid,
            ]);
            $logService->log(
                HealthRoutingLogTypeEnum::ROUTING,
                [
                    'message' => 'Lead is AUH lead, triggering AUH tier routing',
                    'emirates_of_visa' => $this->lead->emirate?->text,
                    'step' => 'AUH check',
                    'is_auh' => true,
                    'source' => $this->source,
                ],
                $this->lead->id,
                $this->lead->uuid,
                null,
                $this->source
            );

            app(HealthTeamRoutingService::class, ['source' => $this->source])
                ->triggerAUHTierRouting($this->lead);
        } else {
            LoggerService::info('Lead is Non AUH lead, triggering Non AUH tier routing',
                ['emirates_of_visa' => $this->lead->emirate?->text, 'source' => $this->source, 'uuid' => $this->lead->uuid]);
            $logService->log(
                HealthRoutingLogTypeEnum::ROUTING,
                [
                    'message' => 'Lead is Non AUH lead, triggering Non AUH tier routing',
                    'emirates_of_visa' => $this->lead->emirate?->text,
                    'step' => 'Non AUH check',
                    'is_auh' => false,
                    'source' => $this->source,
                ],
                $this->lead->id,
                $this->lead->uuid,
                null,
                $this->source
            );

            app(HealthTeamRoutingService::class, ['source' => $this->source])
                ->triggerNonAUHTierRouting($this->lead);
        }

        $this->lead->refresh();
        $this->allocationRequest->setLead($this->lead);

        return $next($request);
    }
}
