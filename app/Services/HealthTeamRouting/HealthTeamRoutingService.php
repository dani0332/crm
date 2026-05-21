<?php

declare(strict_types=1);

namespace App\Services\HealthTeamRouting;

use App\Enums\HealthPlanTypeEnum;
use App\Enums\HealthRoutingLogTypeEnum;
use App\Enums\HealthRoutingSourceEnum;
use App\Enums\HealthTeamType;
use App\Enums\TeamCategoryEnum;
use App\Enums\TeamNameEnum;
use App\Models\HealthQuote;
use App\Services\CanonicalNationalityService;
use App\Services\Logger\LoggerService;
use App\Services\NationalityPoolService;

class HealthTeamRoutingService
{
    use HealthTeamRoutable;

    public function __construct(
        protected HealthTeamRoutingLogService $healthTeamRoutingLogService,
        protected CanonicalNationalityService $canonicalNationalityService,
        protected NationalityPoolService $nationalityPoolService,
        private HealthRoutingSourceEnum $source) {}

    public function triggerAUHTierRouting(HealthQuote $lead)
    {
        // Check if SIC1 (no health plan type)
        if ($lead->isSIC1()) {
            // Terminate
            LoggerService::info('Lead is SIC1 (no health plan type), routing is not applicable', ['source' => $this->source]);
            $this->healthTeamRoutingLogService->log(
                HealthRoutingLogTypeEnum::ROUTING,
                [
                    'message' => 'Lead is SIC1 (no health plan type), routing is not applicable',
                    'step' => 'SIC1 check',
                    'is_sic1' => true,
                    'source' => $this->source,
                ],
                $lead->id,
                $lead->uuid,
                null,
                $this->source
            );

            return;
        }

        // As per business we need to check SIC2 for future
        // For now we will just add logs
        if ($lead->isSIC2()) {
            // Do not terminate just add logs
            LoggerService::info('Lead is SIC2', ['source' => $this->source]);
            $this->healthTeamRoutingLogService->log(
                HealthRoutingLogTypeEnum::ROUTING,
                [
                    'message' => 'Lead is SIC2',
                    'step' => 'SIC2 check',
                    'is_sic2' => true,
                    'source' => $this->source,
                ],
                $lead->id,
                $lead->uuid,
                null,
                $this->source
            );
        }

        // Check if PEC lead identified
        if ($lead->isPECLead() || $lead->hasAnyMemberAgeSixtyOrAbove()) {
            // Do not terminate just add logs
            LoggerService::info('PEC lead identified', ['source' => $this->source, 'pec_marked_at' => $lead->pec_marked_at]);
            $this->healthTeamRoutingLogService->log(
                HealthRoutingLogTypeEnum::ROUTING,
                [
                    'message' => 'PEC lead identified',
                    'step' => 'PEC lead check',
                    'is_pec' => true,
                    'pec_marked_at' => $lead->pec_marked_at,
                    'source' => $this->source,
                ],
                $lead->id,
                $lead->uuid,
                null,
                $this->source
            );

            if ($lead->health_plan_type_id == HealthPlanTypeEnum::ENTRY_LEVEL->value || $lead->health_plan_type_id == HealthPlanTypeEnum::GOOD->value) {
                LoggerService::info('Now checking for Health Plan type Entry Level or Good', ['source' => $this->source, 'pec_marked_at' => $lead->pec_marked_at]);
                $this->healthTeamRoutingLogService->log(
                    HealthRoutingLogTypeEnum::ROUTING,
                    [
                        'message' => 'Now checking for Health Plan type Entry Level or Good',
                        'step' => 'Health Plan type check',
                        'health_plan_type_id' => $lead->health_plan_type_id,
                        'source' => $this->source,
                    ],
                    $lead->id,
                    $lead->uuid,
                    null,
                    $this->source
                );
            } elseif ($lead->health_plan_type_id == HealthPlanTypeEnum::BEST->value) {

                LoggerService::info('Now checking for GBP qualification If Health Plan type is Best', ['source' => $this->source]);
                $this->healthTeamRoutingLogService->log(
                    HealthRoutingLogTypeEnum::ROUTING,
                    [
                        'message' => 'Now checking for GBP qualification If Health Plan type is Best',
                        'step' => 'GBP qualification check',
                        'health_plan_type_id' => $lead->health_plan_type_id,
                        'source' => $this->source,
                    ],
                    $lead->id,
                    $lead->uuid,
                    null,
                    $this->source
                );
                // Check if GBP qualified
                if ($this->isGBPQualified($lead)) {
                    $lead->health_team_type = HealthTeamType::GBP;
                    $lead->save();

                    LoggerService::info('GBP team qualified, assigned GBP team', [
                        'source' => $this->source,
                        'premium' => $lead->price_starting_from,
                        'nationality' => $lead->nationality_id,
                        'team_name' => HealthTeamType::GBP,
                    ]);
                    $this->healthTeamRoutingLogService->log(
                        HealthRoutingLogTypeEnum::ROUTING,
                        [
                            'message' => 'GBP team qualified, assigned to GBP team',
                            'step' => 'GBP team check',
                            'is_gbp' => true,
                            'premium' => $lead->price_starting_from,
                            'nationality' => $lead->nationality_id,
                            'team_name' => HealthTeamType::GBP,
                            'source' => $this->source,
                        ],
                        $lead->id,
                        $lead->uuid,
                        null,
                        $this->source
                    );

                    return;
                }

            }
        }

        // Assign AUH team
        $team = $this->fetchTeamByPriceAndCategory($lead->price_starting_from, TeamCategoryEnum::AUH);
        if (! $team) {
            LoggerService::warning('No AUH team found for given premium', ['premium' => $lead->price_starting_from]);
            $this->healthTeamRoutingLogService->log(
                HealthRoutingLogTypeEnum::ROUTING,
                [
                    'message' => 'No AUH team found for given premium',
                    'step' => 'AUH team check',
                    'premium' => $lead->price_starting_from,
                    'source' => $this->source,
                ],
                $lead->id,
                $lead->uuid,
                null,
                $this->source
            );

            return;
        }

        $lead->health_team_type = $team->name;
        $lead->save();

        LoggerService::info('Assigned AUH team', [
            'source' => $this->source,
            'pec_marked_at' => $lead->pec_marked_at,
            'premium' => $lead->price_starting_from,
            'team_name' => $team->name,
            'min_price' => $team->min_price,
            'max_price' => $team->max_price,
        ]);
        $this->healthTeamRoutingLogService->log(
            HealthRoutingLogTypeEnum::ROUTING,
            [
                'message' => 'Assigned AUH team',
                'step' => 'AUH team check',
                'pec_marked_at' => $lead->pec_marked_at,
                'premium' => $lead->price_starting_from,
                'team_name' => $team->name,
                'min_price' => $team->min_price,
                'max_price' => $team->max_price,
                'is_auh' => true,
                'source' => $this->source,
            ],
            $lead->id,
            $lead->uuid,
            null,
            $this->source
        );
    }
    public function isGBPQualified(HealthQuote $lead): bool
    {
        $gbpMinPrice = $this->getGbpTeamMinPrice();
        LoggerService::info('GBP team min price', ['gbp_min_price' => $gbpMinPrice]);

        // Return if not fall under price
        if ($gbpMinPrice !== null && ! empty($lead->price_starting_from) && $lead->price_starting_from < $gbpMinPrice) {
            return false;
        }

        // Check nationality among nationality pool
        $canonicalNationality = $this->canonicalNationalityService->getByNationalityId($lead->nationality_id);
        $nationalityPoolCodes = $this->nationalityPoolService->getNationalityCodes();

        if (! $nationalityPoolCodes || empty($nationalityPoolCodes->canonical_nationality_codes) || ! $canonicalNationality) {
            return false;
        }

        if (! collect(explode(',', $nationalityPoolCodes->canonical_nationality_codes))
            ->contains($canonicalNationality->canonical_nationality_code)) {
            return false;
        }

        return true;
    }

    public function triggerNonAUHTierRouting(HealthQuote $lead)
    {
        // Check if SIC1 (no health plan type)
        if ($lead->isSIC1()) {
            // Terminate
            LoggerService::info('Lead is SIC1 (no health plan type), routing is not applicable', ['source' => $this->source]);
            $this->healthTeamRoutingLogService->log(
                HealthRoutingLogTypeEnum::ROUTING,
                [
                    'message' => 'Lead is SIC1 (no health plan type), routing is not applicable',
                    'step' => 'SIC1 check',
                    'is_sic1' => true,
                    'source' => $this->source,
                ],
                $lead->id,
                $lead->uuid,
                null,
                $this->source
            );

            return;
        }

        // Check if SIC2
        if ($lead->isSIC2()) {

            // Do not terminate just add logs
            LoggerService::info('Lead is SIC2', ['source' => $this->source]);
            $this->healthTeamRoutingLogService->log(
                HealthRoutingLogTypeEnum::ROUTING,
                [
                    'message' => 'Lead is SIC2',
                    'step' => 'SIC2 check',
                    'is_sic2' => true,
                    'source' => $this->source,
                ],
                $lead->id,
                $lead->uuid,
                null,
                $this->source
            );
        }

        if ($lead->hasAnyMemberAgeSixtyOrAbove() || $lead->isPECLead()) {

            LoggerService::info('PEC lead identified, assigned Non AUH PEC team', [
                'source' => $this->source,
                'pec_marked_at' => $lead->pec_marked_at,
                'team_name' => TeamNameEnum::PEC,
            ]);
            $this->healthTeamRoutingLogService->log(
                HealthRoutingLogTypeEnum::ROUTING,
                [
                    'message' => 'PEC lead identified or any member age 60+ identified, assigned Non AUH PEC team',
                    'step' => 'PEC lead check',
                    'is_pec' => true,
                    'pec_marked_at' => $lead->pec_marked_at,
                    'any_member_age_60_plus' => $lead->hasAnyMemberAgeSixtyOrAbove(),
                    'source' => $this->source,
                    'team_name' => TeamNameEnum::PEC,
                ],
                $lead->id,
                $lead->uuid,
                null,
                $this->source
            );

            if ($lead->health_plan_type_id == HealthPlanTypeEnum::ENTRY_LEVEL->value || $lead->health_plan_type_id == HealthPlanTypeEnum::GOOD->value) {
                // Assign Non AUH PEC Team

                $lead->health_team_type = TeamNameEnum::PEC;
                $lead->save();
                LoggerService::info('Entry Level or Good team assigned, assigned Non AUH PEC team', ['source' => $this->source]);
                $this->healthTeamRoutingLogService->log(
                    HealthRoutingLogTypeEnum::ROUTING,
                    [
                        'message' => 'Entry Level or Good team assigned, assigned Non AUH PEC team',
                        'step' => 'Health Plan type check',
                        'health_plan_type_id' => $lead->health_plan_type_id,
                        'source' => $this->source,
                    ],
                    $lead->id,
                    $lead->uuid,
                    null,
                    $this->source
                );

                return;
            } elseif ($lead->health_plan_type_id == HealthPlanTypeEnum::BEST->value) {
                LoggerService::info('Now checking for GBP qualification If Health Plan type is Best', ['source' => $this->source]);
                $this->healthTeamRoutingLogService->log(
                    HealthRoutingLogTypeEnum::ROUTING,
                    [
                        'message' => 'Now checking for GBP qualification If Health Plan type is Best',
                        'step' => 'GBP qualification check',
                        'health_plan_type_id' => $lead->health_plan_type_id,
                        'source' => $this->source,
                    ],
                    $lead->id,
                    $lead->uuid,
                    null,
                    $this->source
                );

                // Check if GBP qualified
                if ($this->isGBPQualified($lead)) {
                    $lead->health_team_type = HealthTeamType::GBP;
                    $lead->save();

                    LoggerService::info('GBP team qualified, assigned GBP team', [
                        'source' => $this->source,
                        'premium' => $lead->price_starting_from,
                        'nationality' => $lead->nationality_id,
                        'team_name' => HealthTeamType::GBP,
                    ]);
                    $this->healthTeamRoutingLogService->log(
                        HealthRoutingLogTypeEnum::ROUTING,
                        [
                            'message' => 'GBP team qualified, assigned GBP team',
                            'step' => 'GBP team check',
                            'is_gbp' => true,
                            'premium' => $lead->price_starting_from,
                            'nationality' => $lead->nationality_id,
                            'team_name' => HealthTeamType::GBP,
                            'source' => $this->source,
                        ],
                        $lead->id,
                        $lead->uuid,
                        null,
                        $this->source
                    );

                    return;
                } else {
                    LoggerService::info('GBP team not qualified, assigned Non AUH Best team', [
                        'source' => $this->source,
                        'premium' => $lead->price_starting_from,
                        'nationality' => $lead->nationality_id,
                        'team_name' => HealthTeamType::RM_NB,
                    ]);

                    $this->healthTeamRoutingLogService->log(
                        HealthRoutingLogTypeEnum::ROUTING,
                        [
                            'message' => 'GBP team not qualified, assigned Non AUH Best team',
                            'step' => 'GBP team check',
                            'is_gbp' => false,
                            'premium' => $lead->price_starting_from,
                            'nationality' => $lead->nationality_id,
                            'team_name' => HealthTeamType::RM_NB,
                            'source' => $this->source,
                        ],
                        $lead->id,
                        $lead->uuid,
                        null,
                        $this->source
                    );

                }

            }
        } else {
            // Get notional team
            $notionalTeam = $this->getNotionalTeam($lead);
            $lead->notional_team = $notionalTeam;
            $lead->save();

            // Terminate with logs about storing notional team
            LoggerService::info("Non PEC lead identified, Notional team: {$notionalTeam}",
                ['source' => $this->source, 'pec_marked_at' => $lead->pec_marked_at]);
            $this->healthTeamRoutingLogService->log(
                HealthRoutingLogTypeEnum::ROUTING,
                [
                    'message' => "Non PEC lead identified, Team: {$notionalTeam}",
                    'step' => 'Non PEC lead check',
                    'is_non_pec' => true,
                    'pec_marked_at' => $lead->pec_marked_at,
                    'team_name' => $notionalTeam,
                    'source' => $this->source,
                ],
                $lead->id,
                $lead->uuid,
                null,
                $this->source
            );

            return;
        }

        // Assign based on intent (health plan type) non auh best team
        $team = HealthPlanTypeEnum::toTeamNameEnum($lead->health_plan_type_id);
        $lead->health_team_type = $team;
        $lead->save();

        LoggerService::info("{$team} team assigned based on plan type", [
            'source' => $this->source,
            'health_plan_type_id' => $lead->health_plan_type_id,
            'health_plan_type' => HealthPlanTypeEnum::typeText($lead->health_plan_type_id),
            'team_name' => $team,
        ]);
        $this->healthTeamRoutingLogService->log(
            HealthRoutingLogTypeEnum::ROUTING,
            [
                'message' => "{$team} team assigned based on plan type",
                'step' => 'health_plan_type_check',
                'health_plan_type_id' => $lead->health_plan_type_id,
                'health_plan_type' => HealthPlanTypeEnum::typeText($lead->health_plan_type_id),
                'team_name' => $team,
                'source' => $this->source,
            ],
            $lead->id,
            $lead->uuid,
            null,
            $this->source
        );
    }

    private function getNotionalTeam($lead): ?string
    {
        // First check if its GBP qualified
        if ($this->isGBPQualified($lead)) {
            return HealthTeamType::GBP;
        }

        // Else intent based (health plan type)
        return HealthPlanTypeEnum::toTeamNameEnum($lead->health_plan_type_id);
    }
}
