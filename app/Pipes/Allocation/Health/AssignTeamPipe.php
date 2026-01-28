<?php

namespace App\Pipes\Allocation\Health;

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

        // If the lead is not SIC, assign the team based on the health team routing
        if (! $isSIC) {

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
        }

        $healthTeam = $this->fetchTeamByPriceAndCategory($priceStartingFrom, $category);

        if ($healthTeam) {
            LoggerService::info("Filtered team is: {$healthTeam->name}", [
                'team_id' => $healthTeam->id,
                'team_name' => $healthTeam->name,
                'category' => $category->value,
                'price_starting_from' => $priceStartingFrom,
            ]);

            // Special handling: If GBP team and more than 2 members, assign to RM_NB instead
            $this->lead->health_team_type = ($healthTeam->name === HealthTeamType::GBP && $this->lead->members->count() > 2)
                ? HealthTeamType::RM_NB
                : $healthTeam->name;
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
            LoggerService::info("Plan {$planStatus} with plan id: {$this->lead->plan_id} | premium: {$this->lead->premium}");
        } else {
            $price = $this->lead->price_starting_from;
            LoggerService::info("No SIC lead | plan id: {$this->lead->plan_id} | premium: {$this->lead->premium}");
        }

        return $price;
    }
}
