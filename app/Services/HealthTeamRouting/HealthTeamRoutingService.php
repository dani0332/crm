<?php

declare(strict_types=1);

namespace App\Services\HealthTeamRouting;

use App\Enums\HealthRoutingSourceEnum;
use App\Enums\HealthTeamType;
use App\Enums\TeamCategoryEnum;
use App\Enums\TeamNameEnum;
use App\Enums\TeamTypeEnum;
use App\Models\HealthQuote;
use App\Models\Team;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;

class HealthTeamRoutingService
{
    use HealthTeamRoutable;

    public function __construct(
        protected HealthTeamRoutingLogService $healthTeamRoutingLogService,
        protected CanonicalNationalityService $canonicalNationalityService,
        protected NationalityPoolService $nationalityPoolService,
        private HealthRoutingSourceEnum $source) {}

    public function isGBPQualified(HealthQuote $lead): bool
    {
        $gbpMinPrice = $this->getGbpTeamMinPrice();
        // Return if not fall under price
        if ($gbpMinPrice !== null && ! empty($lead->price_starting_from) && $lead->price_starting_from < $gbpMinPrice) {
            return false;
        }

        // Check nationality among nationality pool
        $canonicalNationality = $this->canonicalNationalityService->getByNationalityId($lead->nationality_id);
        $nationalityPoolCodes = $this->nationalityPoolService->getNationalityCodes();

        if (! collect(explode(',', $nationalityPoolCodes))->contains($canonicalNationality->canonical_nationality_code)) {
            return false;
        }

        return true;
    }
    /*
    public function getTeamBasedOnHealthTeamRouting(HealthQuote $lead): ?string
    {
        LoggerService::startQuoteLogging($lead);

        $teamName = '';

        // Step 1: Check if health team routing is enabled
        if (! $this->isHealthTeamRoutingEnabled()) {
            $this->logStep(
                'Health team routing is not enabled',
                'feature_flag_check',
                ['routing_enabled' => false],
                $lead,
                $this->source
            );
            LoggerService::info('Health team routing is not enabled', ['source' => $this->source]);

            return $teamName;
        }

        $this->logStep(
            'Health team routing is enabled',
            'feature_flag_check',
            ['routing_enabled' => true],
            $lead,
            $this->source
        );
        LoggerService::info('Health team routing is enabled', ['source' => $this->source]);

        // Step 2: Determine geography (AUH vs Non-AUH)
        // Validate emirate data
        if (empty($lead->emirate_of_your_visa_id)) {
            $this->logStep(
                'Emirate of visa is missing, cannot determine routing path',
                'validation_error',
                ['emirate_of_visa_id' => null],
                $lead,
                $this->source
            );
            LoggerService::warning('Emirate of visa missing for lead', ['lead_id' => $lead->id, 'source' => $this->source]);

            return $teamName;
        }

        $isAUHLead = $lead->isAUHLead() || $lead->isAUHLead(false);

        if ($isAUHLead) {
            $this->logStep(
                'AUH lead detected, routing to AUH path',
                'geography_detection',
                [
                    'is_auh_lead' => true,
                    'emirate_of_visa_id' => $lead->emirate_of_your_visa_id,
                    'routing_path' => 'AUH',
                ],
                $lead,
                $this->source
            );
            LoggerService::info('AUH lead detected, routing to AUH path', ['emirate_of_visa_id' => $lead->emirate_of_your_visa_id, 'source' => $this->source]);
            $teamName = $this->getTeamBasedOnAUHLead($lead);
        } else {
            $this->logStep(
                'Non-AUH lead detected, routing to Non-AUH path',
                'geography_detection',
                [
                    'is_auh_lead' => false,
                    'emirate_of_visa_id' => $lead->emirate_of_your_visa_id,
                    'routing_path' => 'NON_AUH',
                ],
                $lead,
                $this->source
            );
            LoggerService::info('Non-AUH lead detected, routing to Non-AUH path', ['emirate_of_visa_id' => $lead->emirate_of_your_visa_id, 'source' => $this->source]);
            $teamName = $this->getTeamBasedOnNonAUHLead($lead);
        }

        // Step 3: Log final team selection
        if ($teamName) {
            $this->logStep(
                'Team successfully assigned via health team routing',
                'team_assigned',
                [
                    'team_name' => $teamName,
                    'routing_completed' => true,
                ],
                $lead,
                $this->source
            );
            LoggerService::info('Team successfully assigned via health team routing', ['team_name' => $teamName, 'source' => $this->source]);
        } else {
            $this->logStep(
                'No team found during health team routing',
                'team_assigned',
                [
                    'team_name' => null,
                    'routing_completed' => false,
                ],
                $lead,
                $this->source
            );
            LoggerService::warning('No team found during health team routing', ['source' => $this->source]);
        }

        return $teamName;
    }

    private function getTeamBasedOnAUHLead(HealthQuote $lead): ?string
    {
        $this->logStep(
            'Starting AUH tier-based team routing',
            'auh_tier_routing',
            ['category' => TeamCategoryEnum::AUH->value],
            $lead,
            $this->source
        );
        LoggerService::info('Starting AUH tier-based team routing', ['premium' => $lead->price_starting_from, 'source' => $this->source]);

        // Validate premium data
        if (empty($lead->price_starting_from)) {
            $this->logStep(
                'Premium missing, cannot determine AUH tier',
                'validation_error',
                [
                    'premium' => null,
                    'lead_id' => $lead->id,
                    'lead_uuid' => $lead->uuid,
                    'category' => TeamCategoryEnum::AUH->value,
                ],
                $lead,
                $this->source
            );
            LoggerService::warning('Premium missing for AUH lead', ['lead_id' => $lead->id, 'source' => $this->source]);

            return '';
        }

        // Check if priceStartingFrom is greater than or equal to GBP team's min price
        $gbpMinPrice = $this->getGbpTeamMinPrice();
        if ($gbpMinPrice !== null && $lead->price_starting_from >= $gbpMinPrice) {
            $this->logStep(
                'Price starting from is greater than or equal to GBP min price, assigning to GBP team',
                'gbp_assignment',
                [
                    'price_starting_from' => $lead->price_starting_from,
                    'gbp_min_price' => $gbpMinPrice,
                    'category' => TeamCategoryEnum::AUH->value,
                ],
                $lead,
                $this->source
            );
            LoggerService::info("Price starting from ({$lead->price_starting_from}) is greater than or equal to GBP min price ({$gbpMinPrice}), assigning to GBP team", ['source' => $this->source]);

            return HealthTeamType::GBP;
        }
        $this->logGbpCheckResult($gbpMinPrice, $lead->price_starting_from, 'tier-based');

        try {
            $team = $this->fetchTeamByPriceAndCategory($lead->price_starting_from, TeamCategoryEnum::AUH);

            if ($team) {
                $this->logStep(
                    'AUH tier team matched successfully',
                    'auh_tier_matched',
                    [
                        'team_name' => $team->name,
                        'team_id' => $team->id,
                        'premium' => $lead->price_starting_from,
                        'min_price' => $team->min_price,
                        'max_price' => $team->max_price,
                        'category' => TeamCategoryEnum::AUH->value,
                    ],
                    $lead,
                    $this->source
                );
                LoggerService::info('AUH tier team matched successfully', [
                    'team_name' => $team->name,
                    'team_id' => $team->id,
                    'premium' => $lead->price_starting_from,
                    'source' => $this->source,
                ]);
            } else {
                $this->logStep(
                    'No AUH tier team found for given premium',
                    'auh_tier_matched',
                    [
                        'team_name' => null,
                        'premium' => $lead->price_starting_from,
                        'category' => TeamCategoryEnum::AUH->value,
                    ],
                    $lead,
                    $this->source
                );
                LoggerService::warning('No AUH tier team found for given premium', ['premium' => $lead->price_starting_from, 'source' => $this->source]);
            }

            return $team?->name;
        } catch (\Exception $e) {
            $this->logStep(
                'Exception occurred during AUH tier routing',
                'error',
                [
                    'error_message' => $e->getMessage(),
                    'category' => TeamCategoryEnum::AUH->value,
                ],
                $lead,
                $this->source
            );
            LoggerService::warning('Exception occurred during AUH tier routing', [
                'lead_id' => $lead->id,
                'error_message' => $e->getMessage(),
                'category' => TeamCategoryEnum::AUH->value,
                'source' => $this->source,
            ]);

            return null;
        }
    }

    private function getTeamBasedOnNonAUHLead(HealthQuote $lead): ?string
    {
        // Step 1: Check GBP team min price
        $this->logStep(
            'Checking GBP team min price for Non-AUH lead as first step',
            'gbp_check',
            [],
            $lead,
            $this->source
        );
        LoggerService::info('Checking GBP team min price for Non-AUH lead as first step', [
            'lead_uuid' => $lead->uuid,
            'premium' => $lead->price_starting_from,
            'source' => $this->source,
        ]);

        $gbpMinPrice = $this->getGbpTeamMinPrice();
        if ($gbpMinPrice !== null && ! empty($lead->price_starting_from) && $lead->price_starting_from >= $gbpMinPrice) {
            $this->logStep(
                'Price starting from is greater than or equal to GBP min price, assigning to GBP team',
                'gbp_assignment',
                ['price_starting_from' => $lead->price_starting_from, 'gbp_min_price' => $gbpMinPrice],
                $lead,
                $this->source
            );

            LoggerService::info("Price starting from ({$lead->price_starting_from}) is greater than or equal to GBP min price ({$gbpMinPrice}), assigning to GBP team", [
                'lead_uuid' => $lead->uuid,
                'source' => $this->source,
            ]);

            return HealthTeamType::GBP;
        }

        // Step 2: Check PEC flag
        $isPECLead = $lead->has_pec_tag;
        $this->logStep(
            'Checking PEC flag for Non-AUH lead',
            'pec_check',
            [
                'is_pec_lead' => $isPECLead,
                'pec_marked_at' => $lead->pec_marked_at ? Carbon::parse($lead->pec_marked_at)->format('Y-m-d H:i:s') : null,
            ],
            $lead,
            $this->source
        );
        LoggerService::info('Checking PEC flag for Non-AUH lead', ['is_pec_lead' => $isPECLead, 'source' => $this->source]);

        if ($isPECLead) {
            try {
                $team = $this->fetchPecTeamName($lead);

                $this->logStep(
                    'PEC lead detected, routing to Non-AUH PEC team',
                    'pec_team_selected',
                    [
                        'team_name' => $team,
                        'is_pec_lead' => true,
                        'tier_routing_skipped' => true,
                        'premium' => $lead->price_starting_from,
                    ],
                    $lead,
                    $this->source
                );
                LoggerService::info('PEC lead detected, routing to Non-AUH PEC team', ['team_name' => $team, 'source' => $this->source]);

                return $team;
            } catch (\Exception $e) {
                $this->logStep(
                    'Exception occurred while fetching PEC team',
                    'error',
                    [
                        'error_message' => $e->getMessage(),
                        'is_pec_lead' => true,
                    ],
                    $lead,
                    $this->source
                );
                LoggerService::warning('Exception occurred while fetching PEC team', [
                    'lead_id' => $lead->id,
                    'error_message' => $e->getMessage(),
                    'is_pec_lead' => true,
                    'source' => $this->source,
                ]);

                return null;
            }
        }

        // Step 3: Apply tier-based routing for non-PEC leads
        $this->logStep(
            'Non-PEC lead, applying tier-based routing',
            'non_auh_tier_routing',
            [
                'is_pec_lead' => false,
                'category' => TeamCategoryEnum::NON_AUH->value,
            ],
            $lead,
            $this->source
        );
        LoggerService::info('Non-PEC lead, applying tier-based routing', ['premium' => $lead->price_starting_from, 'source' => $this->source]);

        try {
            $team = $this->fetchTeamByPriceAndCategory($lead->price_starting_from, TeamCategoryEnum::NON_AUH);

            if ($team) {
                $this->logStep(
                    'Non-AUH tier team matched successfully',
                    'non_auh_tier_matched',
                    [
                        'team_name' => $team->name,
                        'team_id' => $team->id,
                        'premium' => $lead->price_starting_from,
                        'min_price' => $team->min_price,
                        'max_price' => $team->max_price,
                        'category' => TeamCategoryEnum::NON_AUH->value,
                    ],
                    $lead,
                    $this->source
                );
                LoggerService::info('Non-AUH tier team matched successfully', [
                    'team_name' => $team->name,
                    'team_id' => $team->id,
                    'premium' => $lead->price_starting_from,
                    'source' => $this->source,
                ]);
            } else {
                $this->logStep(
                    'No Non-AUH tier team found for given premium',
                    'non_auh_tier_matched',
                    [
                        'team_name' => null,
                        'premium' => $lead->price_starting_from,
                        'category' => TeamCategoryEnum::NON_AUH->value,
                    ],
                    $lead,
                    $this->source
                );
                LoggerService::warning('No Non-AUH tier team found for given premium', ['premium' => $lead->price_starting_from, 'source' => $this->source]);
            }

            return $team?->name;
        } catch (\Exception $e) {
            $this->logStep(
                'Exception occurred during Non-AUH tier routing',
                'error',
                [
                    'error_message' => $e->getMessage(),
                    'category' => TeamCategoryEnum::NON_AUH->value,
                ],
                $lead,
                $this->source
            );
            LoggerService::warning('Exception occurred during Non-AUH tier routing', [
                'lead_id' => $lead->id,
                'error_message' => $e->getMessage(),
                'category' => TeamCategoryEnum::NON_AUH->value,
                'source' => $this->source,
            ]);

            return null;
        }
    }

    private function fetchPecTeamName(HealthQuote $lead): ?string
    {
        $team = Team::where('allocation_threshold_enabled', true)
            ->where('type', TeamTypeEnum::TEAM)
            ->where('name', TeamNameEnum::PEC)
            ->active()
            ->first();

        if ($team) {
            $this->logStep(
                'Non-AUH PEC team found',
                'pec_team_found',
                [
                    'team_name' => $team->name,
                    'team_id' => $team->id,
                ],
                $lead,
                $this->source
            );
            LoggerService::info('Non-AUH PEC team found', ['team_name' => $team->name, 'team_id' => $team->id]);
        } else {
            $this->logStep(
                'Non-AUH PEC team not found',
                'pec_team_found',
                ['team_name' => null],
                $lead,
                $this->source
            );
            LoggerService::warning('Non-AUH PEC team not found');
        }

        return $team?->name;
    }*/
}
