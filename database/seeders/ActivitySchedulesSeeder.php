<?php

namespace Database\Seeders;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use App\Enums\TeamTypeEnum;
use App\Models\ActivitySchedule;
use App\Models\Role;
use App\Models\Team;
use Illuminate\Database\Seeder;

class ActivitySchedulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $teamsArray =
        $rolesArray = [];
        $rolesUsedInActivities = [
            RolesEnum::RMAdvisor,
            RolesEnum::EBPAdvisor,
            RolesEnum::HealthManager,
            RolesEnum::CorplineManager,
            RolesEnum::CorpLineAdvisor,
            RolesEnum::HomeManager,
            RolesEnum::HomeAdvisor,
            RolesEnum::PetManager,
            RolesEnum::PetAdvisor,
            RolesEnum::CycleManager,
            RolesEnum::CycleAdvisor,
            RolesEnum::YachtManager,
            RolesEnum::YachtAdvisor,
        ];

        $teamsUsedInActivities = [
            ['parent_team' => TeamNameEnum::HEALTH, 'team' => TeamNameEnum::RM_NB],
            ['parent_team' => TeamNameEnum::HEALTH, 'team' => TeamNameEnum::RM_SPEED],
            ['parent_team' => TeamNameEnum::HEALTH, 'team' => TeamNameEnum::EBP],
            ['parent_team' => TeamNameEnum::HEALTH, 'team' => TeamNameEnum::RM_RENEWALS],
            ['parent_team' => TeamNameEnum::CORPLINE, 'team' => TeamNameEnum::CORPLINE_TEAM],
            ['parent_team' => TeamNameEnum::CORPLINE, 'team' => TeamNameEnum::CORPLINE_RENEWALS],
            ['parent_team' => TeamNameEnum::HOME, 'team' => TeamNameEnum::HOME],
            ['parent_team' => TeamNameEnum::HOME, 'team' => TeamNameEnum::HOME_RENEWALS],
            ['parent_team' => TeamNameEnum::PET, 'team' => TeamNameEnum::PET_TEAM],
            ['parent_team' => TeamNameEnum::PET, 'team' => TeamNameEnum::PET_RENEWALS],
            ['parent_team' => TeamNameEnum::CYCLE, 'team' => TeamNameEnum::CYCLE],
            ['parent_team' => TeamNameEnum::CYCLE, 'team' => TeamNameEnum::CYCLE_RENEWALS],
            ['parent_team' => TeamNameEnum::YACHT, 'team' => TeamNameEnum::YACHT_TEAM],
            ['parent_team' => TeamNameEnum::YACHT, 'team' => TeamNameEnum::YACHT_RENEWALS],
        ];

        foreach ($rolesUsedInActivities as $role) {
            $rolesArray[$role] = Role::where('name', $role)->first()->id ?? null;
        }

        foreach ($teamsUsedInActivities as $team) {
            $parentTeamId = Team::firstOrCreate(['name' => $team['parent_team'], 'type' => TeamTypeEnum::PRODUCT])->id ?? null;
            $teamsArray[$team['team']] = Team::firstOrCreate(['name' => $team['team'], 'type' => TeamTypeEnum::TEAM], ['parent_team_id' => $parentTeamId])->id ?? null;
        }

        $schedules = [
            [
                'quote_type_id' => QuoteTypeId::Health,
                'roles' => [
                    RolesEnum::RMAdvisor => [
                        'teams' => [
                            TeamNameEnum::RM_NB => [
                                'quote_status' => [
                                    QuoteStatusEnum::FollowedUp => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                        ],
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 2],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],

                                        ],
                                    ],
                                    QuoteStatusEnum::ApplicationPending => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 3],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                            ['name' => '3rd Call Follow-up', 'due_days' => 3],

                                        ],
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                            ['name' => '3rd Call Follow-up', 'due_days' => 5],

                                        ],
                                    ],
                                ],
                            ],
                            TeamNameEnum::RM_SPEED => [
                                'quote_status' => [
                                    QuoteStatusEnum::FollowedUp => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],

                                        ],
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 2],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],

                                        ],
                                    ],
                                    QuoteStatusEnum::ApplicationPending => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 3],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                            ['name' => '3rd Call Follow-up', 'due_days' => 3],

                                        ],
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                            ['name' => '3rd Call Follow-up', 'due_days' => 5],

                                        ],
                                    ],
                                ],
                            ],
                            TeamNameEnum::EBP => [
                                'quote_status' => [
                                    QuoteStatusEnum::FollowedUp => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],

                                        ],
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 2],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],

                                        ],
                                    ],
                                    QuoteStatusEnum::ApplicationPending => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 3],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                            ['name' => '3rd Call Follow-up', 'due_days' => 3],

                                        ],
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                            ['name' => '3rd Call Follow-up', 'due_days' => 5],

                                        ],
                                    ],
                                ],
                            ],
                            TeamNameEnum::RM_RENEWALS => [
                                'quote_status' => [
                                    QuoteStatusEnum::RenewalTermsReceived => [
                                        'activities' => [
                                            ['name' => 'Email Renewal Terms', 'due_days' => 2],

                                        ],
                                    ],
                                    QuoteStatusEnum::Quoted => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 2],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Call Follow-up', 'due_days' => 2],

                                        ],
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 3],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                            ['name' => '3rd Call Follow-up', 'due_days' => 3],

                                        ],
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                            ['name' => '3rd Call Follow-up', 'due_days' => 5],

                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    RolesEnum::EBPAdvisor => [
                        'teams' => [
                            TeamNameEnum::RM_NB => [
                                'quote_status' => [
                                    QuoteStatusEnum::FollowedUp => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],

                                        ],
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 2],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],

                                        ],
                                    ],
                                    QuoteStatusEnum::ApplicationPending => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 3],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                            ['name' => '3rd Call Follow-up', 'due_days' => 3],

                                        ],
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                            ['name' => '3rd Call Follow-up', 'due_days' => 5],

                                        ],
                                    ],
                                ],
                            ],
                            TeamNameEnum::RM_SPEED => [
                                'quote_status' => [
                                    QuoteStatusEnum::FollowedUp => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],

                                        ],
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 2],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],

                                        ],
                                    ],
                                    QuoteStatusEnum::ApplicationPending => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 3],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                            ['name' => '3rd Call Follow-up', 'due_days' => 3],

                                        ],
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                            ['name' => '3rd Call Follow-up', 'due_days' => 5],

                                        ],
                                    ],
                                ],
                            ],
                            TeamNameEnum::EBP => [
                                'quote_status' => [
                                    QuoteStatusEnum::FollowedUp => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],

                                        ],
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 2],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],

                                        ],
                                    ],
                                    QuoteStatusEnum::ApplicationPending => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 3],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                            ['name' => '3rd Call Follow-up', 'due_days' => 3],

                                        ],
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                            ['name' => '3rd Call Follow-up', 'due_days' => 5],

                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    RolesEnum::HealthManager => [
                        'teams' => [
                            TeamNameEnum::RM_NB => [
                                'quote_status' => [
                                    QuoteStatusEnum::FollowedUp => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],

                                        ],
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 2],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],

                                        ],
                                    ],
                                    QuoteStatusEnum::ApplicationPending => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 3],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                            ['name' => '3rd Call Follow-up', 'due_days' => 3],

                                        ],
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                            ['name' => '3rd Call Follow-up', 'due_days' => 5],

                                        ],
                                    ],
                                ],
                            ],
                            TeamNameEnum::RM_SPEED => [
                                'quote_status' => [
                                    QuoteStatusEnum::FollowedUp => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],

                                        ],
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 2],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],

                                        ],
                                    ],
                                    QuoteStatusEnum::ApplicationPending => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 3],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                            ['name' => '3rd Call Follow-up', 'due_days' => 3],

                                        ],
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                            ['name' => '3rd Call Follow-up', 'due_days' => 5],

                                        ],
                                    ],
                                ],
                            ],
                            TeamNameEnum::EBP => [
                                'quote_status' => [
                                    QuoteStatusEnum::FollowedUp => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],

                                        ],
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 2],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],

                                        ],
                                    ],
                                    QuoteStatusEnum::ApplicationPending => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 3],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                            ['name' => '3rd Call Follow-up', 'due_days' => 3],

                                        ],
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                            ['name' => '3rd Call Follow-up', 'due_days' => 5],

                                        ],
                                    ],
                                ],
                            ],
                            TeamNameEnum::RM_RENEWALS => [
                                'quote_status' => [
                                    QuoteStatusEnum::RenewalTermsReceived => [
                                        'activities' => [
                                            ['name' => 'Email Renewal Terms', 'due_days' => 2],

                                        ],
                                    ],
                                    QuoteStatusEnum::Quoted => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 2],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Call Follow-up', 'due_days' => 2],

                                        ],
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 3],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                            ['name' => '3rd Call Follow-up', 'due_days' => 3],

                                        ],
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                            ['name' => '3rd Call Follow-up', 'due_days' => 5],

                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'quote_type_id' => QuoteTypeId::Business,
                'roles' => [
                    RolesEnum::CorplineManager => [
                        'teams' => [
                            TeamNameEnum::CORPLINE_TEAM => [
                                'quote_status' => [
                                    QuoteStatusEnum::ProposalFormRequested => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Follow-up', 'due_days' => 2],

                                        ],
                                    ],
                                    QuoteStatusEnum::AdditionalInformationRequested => [
                                        'activities' => [
                                            ['name' => 'Additional Information 1st Follow-up', 'due_days' => 1],
                                            ['name' => 'Additional Information 2nd Follow-up', 'due_days' => 2],

                                        ],
                                    ],
                                    QuoteStatusEnum::QuoteRequested => [
                                        'activities' => [
                                            ['name' => 'Follow-up Quotes 1', 'due_days' => 2],
                                            ['name' => 'Follow-up Quotes 2', 'due_days' => 2],

                                        ],
                                    ],
                                    QuoteStatusEnum::Quoted => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Follow-up', 'due_days' => 2],

                                        ],
                                    ],
                                    QuoteStatusEnum::FinalizingTerms => [
                                        'activities' => [
                                            ['name' => 'Finalizing Terms 1st Follow-up', 'due_days' => 2],
                                            ['name' => 'Finalizing Terms 2nd Follow-up', 'due_days' => 2],

                                        ],
                                    ],
                                ],
                            ],
                            TeamNameEnum::CORPLINE_RENEWALS => [
                                'quote_status' => [
                                    QuoteStatusEnum::FollowedUp => [
                                        'activities' => [
                                            ['name' => '1st Reminder', 'due_days' => 1],
                                            ['name' => '2nd Reminder', 'due_days' => 2],
                                            ['name' => '3rd Reminder', 'due_days' => 4],
                                            ['name' => '4th Reminder', 'due_days' => 13],

                                        ],
                                    ],
                                    QuoteStatusEnum::PendingRenewalInformation => [
                                        'activities' => [
                                            ['name' => 'Pending Renewal Information Reminder 1', 'due_days' => 2],
                                            ['name' => 'Pending Renewal Information Reminder 2', 'due_days' => 2],
                                            ['name' => 'Pending Renewal Information Reminder 3', 'due_days' => 2],

                                        ],
                                    ],
                                    QuoteStatusEnum::QuoteRequested => [
                                        'activities' => [
                                            ['name' => 'Follow-up Quotes 1', 'due_days' => 2],
                                            ['name' => 'Follow-up Quotes 2', 'due_days' => 2],

                                        ],
                                    ],
                                    QuoteStatusEnum::Quoted => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Follow-up', 'due_days' => 2],

                                        ],
                                    ],
                                    QuoteStatusEnum::FinalizingTerms => [
                                        'activities' => [
                                            ['name' => 'Finalizing Terms 1st Follow-up', 'due_days' => 2],
                                            ['name' => 'Finalizing Terms 2nd Follow-up', 'due_days' => 2],
                                            ['name' => 'Finalizing Terms 3rd Follow-up', 'due_days' => 2],

                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    RolesEnum::CorpLineAdvisor => [
                        'teams' => [
                            TeamNameEnum::CORPLINE_TEAM => [
                                'quote_status' => [
                                    QuoteStatusEnum::ProposalFormRequested => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Follow-up', 'due_days' => 2],

                                        ],
                                    ],
                                    QuoteStatusEnum::AdditionalInformationRequested => [
                                        'activities' => [
                                            ['name' => 'Additional Information 1st Follow-up', 'due_days' => 1],
                                            ['name' => 'Additional Information 2nd Follow-up', 'due_days' => 2],

                                        ],
                                    ],
                                    QuoteStatusEnum::QuoteRequested => [
                                        'activities' => [
                                            ['name' => 'Follow-up Quotes 1', 'due_days' => 2],
                                            ['name' => 'Follow-up Quotes 2', 'due_days' => 2],

                                        ],
                                    ],
                                    QuoteStatusEnum::Quoted => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Follow-up', 'due_days' => 2],

                                        ],
                                    ],
                                    QuoteStatusEnum::FinalizingTerms => [
                                        'activities' => [
                                            ['name' => 'Finalizing Terms 1st Follow-up', 'due_days' => 2],
                                            ['name' => 'Finalizing Terms 2nd Follow-up', 'due_days' => 2],

                                        ],
                                    ],
                                ],
                            ],
                            TeamNameEnum::CORPLINE_RENEWALS => [
                                'quote_status' => [
                                    QuoteStatusEnum::FollowedUp => [
                                        'activities' => [
                                            ['name' => '1st Reminder', 'due_days' => 1],
                                            ['name' => '2nd Reminder', 'due_days' => 2],
                                            ['name' => '3rd Reminder', 'due_days' => 4],
                                            ['name' => '4th Reminder', 'due_days' => 13],

                                        ],
                                    ],
                                    QuoteStatusEnum::PendingRenewalInformation => [
                                        'activities' => [
                                            ['name' => 'Pending Renewal Information Reminder 1', 'due_days' => 2],
                                            ['name' => 'Pending Renewal Information Reminder 2', 'due_days' => 2],
                                            ['name' => 'Pending Renewal Information Reminder 3', 'due_days' => 2],

                                        ],
                                    ],
                                    QuoteStatusEnum::QuoteRequested => [
                                        'activities' => [
                                            ['name' => 'Follow-up Quotes 1', 'due_days' => 2],
                                            ['name' => 'Follow-up Quotes 2', 'due_days' => 2],

                                        ],
                                    ],
                                    QuoteStatusEnum::Quoted => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Follow-up', 'due_days' => 2],

                                        ],
                                    ],
                                    QuoteStatusEnum::FinalizingTerms => [
                                        'activities' => [
                                            ['name' => 'Finalizing Terms 1st Follow-up', 'due_days' => 2],
                                            ['name' => 'Finalizing Terms 2nd Follow-up', 'due_days' => 2],
                                            ['name' => 'Finalizing Terms 3rd Follow-up', 'due_days' => 2],

                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'quote_type_id' => QuoteTypeId::Home,
                'roles' => [
                    RolesEnum::HomeManager => [
                        'teams' => [
                            TeamNameEnum::HOME => [
                                'quote_status' => [
                                    QuoteStatusEnum::Allocated => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],
                                            // ['name' => '3rd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                            ['name' => '3rd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::Quoted => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],
                                            // ['name' => '3rd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                            ['name' => '3rd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 2],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                ],
                            ],
                            TeamNameEnum::HOME_RENEWALS => [
                                'quote_status' => [
                                    QuoteStatusEnum::Allocated => [
                                        'activities' => [
                                            ['name' => 'Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::RenewalTermsSent => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],
                                            // ['name' => '3rd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                            ['name' => '3rd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    RolesEnum::HomeAdvisor => [
                        'teams' => [
                            TeamNameEnum::HOME => [
                                'quote_status' => [
                                    QuoteStatusEnum::Allocated => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],
                                            // ['name' => '3rd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                            ['name' => '3rd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::Quoted => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],
                                            // ['name' => '3rd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                            ['name' => '3rd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 2],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                ],
                            ],
                            TeamNameEnum::HOME_RENEWALS => [
                                'quote_status' => [
                                    QuoteStatusEnum::Allocated => [
                                        'activities' => [
                                            ['name' => 'Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::RenewalTermsSent => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],
                                            // ['name' => '3rd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                            ['name' => '3rd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'quote_type_id' => QuoteTypeId::Pet,
                'roles' => [
                    RolesEnum::PetManager => [
                        'teams' => [
                            TeamNameEnum::PET_TEAM => [
                                'quote_status' => [
                                    QuoteStatusEnum::Allocated => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],
                                            // ['name' => '3rd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                            ['name' => '3rd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::Quoted => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],
                                            // ['name' => '3rd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                            ['name' => '3rd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 2],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                ],
                            ],
                            TeamNameEnum::PET_RENEWALS => [
                                'quote_status' => [
                                    QuoteStatusEnum::Allocated => [
                                        'activities' => [
                                            ['name' => 'Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::RenewalTermsSent => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],
                                            // ['name' => '3rd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                            ['name' => '3rd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    RolesEnum::PetAdvisor => [
                        'teams' => [
                            TeamNameEnum::PET_TEAM => [
                                'quote_status' => [
                                    QuoteStatusEnum::Allocated => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],
                                            // ['name' => '3rd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                            ['name' => '3rd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::Quoted => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],
                                            // ['name' => '3rd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                            ['name' => '3rd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 2],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                ],
                            ],
                            TeamNameEnum::PET_RENEWALS => [
                                'quote_status' => [
                                    QuoteStatusEnum::Allocated => [
                                        'activities' => [
                                            ['name' => 'Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::RenewalTermsSent => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],
                                            // ['name' => '3rd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                            ['name' => '3rd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'quote_type_id' => QuoteTypeId::Cycle,
                'roles' => [
                    RolesEnum::CycleManager => [
                        'teams' => [
                            TeamNameEnum::CYCLE => [
                                'quote_status' => [
                                    QuoteStatusEnum::Allocated => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],
                                            // ['name' => '3rd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                            ['name' => '3rd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::Quoted => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],
                                            // ['name' => '3rd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                            ['name' => '3rd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 2],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                ],
                            ],
                            TeamNameEnum::CYCLE_RENEWALS => [
                                'quote_status' => [
                                    QuoteStatusEnum::Allocated => [
                                        'activities' => [
                                            ['name' => 'Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::RenewalTermsSent => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],
                                            // ['name' => '3rd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                            ['name' => '3rd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    RolesEnum::CycleAdvisor => [
                        'teams' => [
                            TeamNameEnum::CYCLE => [
                                'quote_status' => [
                                    QuoteStatusEnum::Allocated => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],
                                            // ['name' => '3rd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                            ['name' => '3rd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::Quoted => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],
                                            // ['name' => '3rd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                            ['name' => '3rd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 2],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                ],
                            ],
                            TeamNameEnum::CYCLE_RENEWALS => [
                                'quote_status' => [
                                    QuoteStatusEnum::Allocated => [
                                        'activities' => [
                                            ['name' => 'Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::RenewalTermsSent => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],
                                            // ['name' => '3rd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                            ['name' => '3rd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'quote_type_id' => QuoteTypeId::Yacht,
                'roles' => [
                    RolesEnum::YachtManager => [
                        'teams' => [
                            TeamNameEnum::YACHT_TEAM => [
                                'quote_status' => [
                                    QuoteStatusEnum::Allocated => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],
                                            // ['name' => '3rd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                            ['name' => '3rd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::Quoted => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],
                                            // ['name' => '3rd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                            ['name' => '3rd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 2],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                ],
                            ],
                            TeamNameEnum::YACHT_RENEWALS => [
                                'quote_status' => [
                                    QuoteStatusEnum::Allocated => [
                                        'activities' => [
                                            ['name' => 'Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::RenewalTermsSent => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],
                                            // ['name' => '3rd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                            ['name' => '3rd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    RolesEnum::YachtAdvisor => [
                        'teams' => [
                            TeamNameEnum::YACHT_TEAM => [
                                'quote_status' => [
                                    QuoteStatusEnum::Allocated => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],
                                            // ['name' => '3rd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                            ['name' => '3rd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::Quoted => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],
                                            // ['name' => '3rd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                            ['name' => '3rd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 2],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                ],
                            ],
                            TeamNameEnum::YACHT_RENEWALS => [
                                'quote_status' => [
                                    QuoteStatusEnum::Allocated => [
                                        'activities' => [
                                            ['name' => 'Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::RenewalTermsSent => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],
                                            // ['name' => '3rd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                            ['name' => '3rd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            // ['name' => '1st Follow-up', 'due_days' => 1],
                                            // ['name' => '2nd Follow-up', 'due_days' => 2],

                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 1],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($schedules as $schedule) {
            foreach ($schedule['roles'] as $roleKey => $role) {
                foreach ($role['teams'] as $teamKey => $team) {
                    foreach ($team['quote_status'] as $quoteStatusKey => $quoteStatus) {
                        foreach ($quoteStatus['activities'] as $key => $activity) {
                            ActivitySchedule::firstOrCreate([
                                'quote_type_id' => $schedule['quote_type_id'],
                                'quote_status_id' => $quoteStatusKey,
                                'role_id' => $rolesArray[$roleKey],
                                'team_id' => $teamsArray[$teamKey],
                                'name' => $activity['name'],
                                'sorting_order' => ++$key,
                            ], [
                                'description' => $activity['name'],
                                'due_days' => $activity['due_days'],
                            ]);
                        }
                    }
                }
            }
        }
    }
}
