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

        // Check if lead source ! ecom
        if (! $this->lead->isEcommerce()) {
            // Terminate
            LoggerService::info('Lead source is not ecom, health team routing is not applicable', [
                'source' => $this->source,
                'lead_source' => $this->lead->source]);
            $logService->log(
                HealthRoutingLogTypeEnum::ROUTING,
                [
                    'message' => 'Lead source is not ecom, health team routing is not applicable',
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
            LoggerService::info('Health team routing is not enabled', ['source' => $this->source]);
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

        LoggerService::info('------ Health team routing starts ------', ['source' => $this->source]);
        $isAUHLead = $this->lead->isAUHLead(false);

        // AUH path
        if ($isAUHLead) {
            LoggerService::info('Lead is AUH lead, triggering AUH tier routing', ['source' => $this->source]);
            $logService->log(
                HealthRoutingLogTypeEnum::ROUTING,
                [
                    'message' => 'Lead is AUH lead, triggering AUH tier routing',
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
            LoggerService::info('Lead is Non AUH lead, triggering Non AUH tier routing', ['source' => $this->source]);
            $logService->log(
                HealthRoutingLogTypeEnum::ROUTING,
                [
                    'message' => 'Lead is Non AUH lead, triggering Non AUH tier routing',
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
