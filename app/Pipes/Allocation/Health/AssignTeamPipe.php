<?php

namespace App\Pipes\Allocation\Health;

use App\Enums\HealthPlanTypeEnum;
use App\Enums\HealthRoutingLogTypeEnum;
use App\Enums\HealthTeamType;
use App\Enums\TeamCategoryEnum;
use App\Mail\HealthAssignmentIssueEmail;
use App\Models\Team;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\HealthTeamRouting\HealthTeamRoutable;
use App\Services\HealthTeamRouting\HealthTeamRoutingLogService;
use App\Services\HealthTeamRouting\HealthTeamRoutingService;
use App\Services\Logger\LoggerService;
use Closure;
use Illuminate\Support\Facades\Mail;

class AssignTeamPipe extends BaseAllocationPipe
{
    use HealthTeamRoutable;
    /**
     * Handle the incoming request.
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        $logService = app(HealthTeamRoutingLogService::class);
        $isSIC = $this->lead->isSIC($this->allocationRequest->getQuoteType());

        // Priority 1: Check if we should use health plan type for team assignment (regardless of SIC/non-SIC)
        if ($this->shouldUseHealthPlanType()) {
            LoggerService::info('Using health plan type for team assignment');
            $logService->log(
                HealthRoutingLogTypeEnum::ROUTING,
                [
                    'message' => 'Using health plan type for team assignment',
                    'step' => 'health_plan_type_check',
                    'is_sic' => $isSIC,
                    'routing_applicable' => true,
                    'health_plan_type_id' => $this->lead->health_plan_type_id,
                    'quote_type' => $this->allocationRequest->getQuoteType(),
                ],
                $this->lead->id,
                $this->lead->uuid
            );
            $this->updateHealthTeamType();
            $this->lead->refresh();
        } elseif (! $isSIC) {
            // Priority 2: If the lead is not SIC, assign the team based on the health team routing
            LoggerService::info('Non-SIC lead detected, health team routing is applicable');
            $logService->log(
                HealthRoutingLogTypeEnum::ROUTING,
                [
                    'message' => 'Non-SIC lead detected, health team routing is applicable',
                    'step' => 'sic_check',
                    'is_sic' => false,
                    'routing_applicable' => true,
                    'quote_type' => $this->allocationRequest->getQuoteType(),
                ],
                $this->lead->id,
                $this->lead->uuid
            );
            $teamName = app(HealthTeamRoutingService::class)->getTeamBasedOnHealthTeamRouting($this->lead);
            if ($teamName) {
                $this->lead->health_team_type = $teamName;
                $this->lead->save();
            }
        } else {
            // Priority 3: If SIC lead, assign team based on prices
            LoggerService::info('SIC lead detected, health team routing is not applicable. Continuing to assign team based on prices');
            $logService->log(
                HealthRoutingLogTypeEnum::ROUTING,
                [
                    'message' => 'SIC lead detected, health team routing is not applicable',
                    'step' => 'sic_check',
                    'is_sic' => true,
                    'routing_applicable' => false,
                    'quote_type' => $this->allocationRequest->getQuoteType(),
                ],
                $this->lead->id,
                $this->lead->uuid
            );
            $this->assignTeamBasedOnPrices();
        }

        if (! $this->lead->health_team_type) {
            LoggerService::warning('No health team found');
            $this->throw('No health team found', self::NOT_FOUND);
        }

        $this->lead->refresh();

        $this->allocationRequest->setLead($this->lead);

        return $next($request);
    }

    protected function assignTeamBasedOnPrices()
    {
        LoggerService::info('Inside assignTeamBasedOnPrices');

        $priceStartingFrom = $this->determinePriceStartingFrom();

        if ($priceStartingFrom === null) {
            LoggerService::warning('No team found - price starting from is null');
            $this->lead->is_error_email_sent = true;
            $this->lead->save();
            Mail::send(new HealthAssignmentIssueEmail($this->lead->code, $priceStartingFrom));

            return;
        }

        $isAUHLead = $this->lead->isAUHLead() || $this->lead->isAUHLead(false);
        $category = $isAUHLead ? TeamCategoryEnum::AUH : TeamCategoryEnum::NON_AUH;

        // Check if priceStartingFrom is greater than or equal to GBP team's min price (only for AUH leads)
        if ($isAUHLead) {
            LoggerService::info('AUH lead detected, checking GBP team min price');
            $gbpMinPrice = $this->getGbpTeamMinPrice();
            if ($gbpMinPrice !== null && $priceStartingFrom >= $gbpMinPrice) {
                LoggerService::info("Price starting from ({$priceStartingFrom}) is greater than or equal to GBP min price ({$gbpMinPrice}), assigning to GBP team");
                $this->lead->health_team_type = HealthTeamType::GBP;
                $this->lead->save();

                return;
            }
            $this->logGbpCheckResult($gbpMinPrice, $priceStartingFrom, 'price-based');
        } else {
            LoggerService::info('Non-AUH lead detected, assigning team based on price range');
        }

        $healthTeam = $this->fetchTeamByPriceAndCategory($priceStartingFrom, $category);

        if ($healthTeam) {
            LoggerService::info("Filtered team is: {$healthTeam->name}", [
                'team_id' => $healthTeam->id,
                'team_name' => $healthTeam->name,
                'category' => $category->value,
                'price_starting_from' => $priceStartingFrom,
            ]);

            $this->lead->health_team_type = $healthTeam->name;
        } else {
            LoggerService::warning('No team found for the given price range', [
                'price_starting_from' => $priceStartingFrom,
                'category' => $category->value,
            ]);
            $this->lead->is_error_email_sent = true;
            Mail::send(new HealthAssignmentIssueEmail($this->lead->code, $priceStartingFrom));
        }

        $this->lead->save();
    }

    protected function determinePriceStartingFrom()
    {
        if ($this->lead->isSIC($this->allocationRequest->getQuoteType())) {
            $price = ! empty($this->lead->plan_id) && ! empty($this->lead->premium)
                ? $this->lead->premium
                : $this->lead->price_starting_from;

            $planStatus = ! empty($this->lead->plan_id) ? 'found' : 'not found';
            LoggerService::info("Plan {$planStatus} with plan id: {$this->lead->plan_id} | premium: {$price}");
        } else {
            $price = $this->lead->price_starting_from;
            LoggerService::info("No SIC lead | plan id: {$this->lead->plan_id} | premium: {$price}");
        }

        return $price;
    }

    protected function shouldUseHealthPlanType(): bool
    {
        $isAUHLead = $this->lead->isAUHLead() || $this->lead->isAUHLead(false);
        $isPECLead = $this->lead->has_pec_tag;

        return ! $isAUHLead && ! $isPECLead;
    }

    protected function updateHealthTeamType()
    {
        if (! $this->lead->health_plan_type_id) {
            LoggerService::warning('No health_plan_type_id found on lead');
            $this->throw('No health plan type ID found', self::NOT_FOUND);
        }

        $teamNameEnumValue = HealthPlanTypeEnum::toTeamNameEnum($this->lead->health_plan_type_id);

        if (! $teamNameEnumValue) {
            LoggerService::warning("Could not map health plan type ID '{$this->lead->health_plan_type_id}' to TeamNameEnum");
            $this->throw("Unable to map health plan type ID: {$this->lead->health_plan_type_id}", self::NOT_FOUND);
        }

        $this->lead->health_team_type = $teamNameEnumValue;
        $this->lead->save();

        $healthPlanTypeText = HealthPlanTypeEnum::typeText($this->lead->health_plan_type_id);
        LoggerService::info("Health team type updated to {$teamNameEnumValue} based on health plan type ID: {$this->lead->health_plan_type_id} ({$healthPlanTypeText})");
    }
}
