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
            RolesEnum::YachtAdvisor
        ];

        $teamsUsedInActivities = [
            TeamNameEnum::RM_NB,
            TeamNameEnum::RM_SPEED,
            TeamNameEnum::EBP,
            TeamNameEnum::RM_RENEWALS,
            TeamNameEnum::CORPLINE_TEAM,
            TeamNameEnum::RENEWALS, // it should be updated as per corpline renewals,
            TeamNameEnum::NO_TEAM // Add this for null team as per requirement
        ];

        foreach($rolesUsedInActivities as $role) {
            $rolesArray[$role] = Role::where('name', $role)->first()->id ?? null;
        }

        foreach($teamsUsedInActivities as $team) {
            $teamsArray[$team] = Team::where([
                'name' => $team,
                'type' => TeamTypeEnum::TEAM
            ])->first()->id ?? null;
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
                                        ]
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 2],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                        ]
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
                                        ]
                                    ],
                                ]
                            ],
                            TeamNameEnum::RM_SPEED => [
                                'quote_status' => [
                                    QuoteStatusEnum::FollowedUp => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                        ]
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 2],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                        ]
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
                                        ]
                                    ],
                                ]
                            ],
                            TeamNameEnum::EBP => [
                                'quote_status' => [
                                    QuoteStatusEnum::FollowedUp => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                        ]
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 2],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                        ]
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
                                        ]
                                    ],
                                ]
                            ],
                            TeamNameEnum::RM_RENEWALS => [
                                'quote_status' => [
                                    QuoteStatusEnum::RenewalTermsReceived => [
                                        'activities' => [
                                            ['name' => 'Email renewal terms', 'due_days' => 2]
                                        ]
                                    ],
                                    QuoteStatusEnum::Quoted => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 2],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Call Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 3],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                            ['name' => '3rd Call Follow-up', 'due_days' => 3],
                                        ]
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                            ['name' => '3rd Call Follow-up', 'due_days' => 5],
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    RolesEnum::EBPAdvisor => [
                        'teams' => [
                            TeamNameEnum::RM_NB => [
                                'quote_status' => [
                                    QuoteStatusEnum::FollowedUp => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                        ]
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 2],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                        ]
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
                                        ]
                                    ],
                                ]
                            ],
                            TeamNameEnum::RM_SPEED => [
                                'quote_status' => [
                                    QuoteStatusEnum::FollowedUp => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                        ]
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 2],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                        ]
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
                                        ]
                                    ],
                                ]
                            ],
                            TeamNameEnum::EBP => [
                                'quote_status' => [
                                    QuoteStatusEnum::FollowedUp => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                        ]
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 2],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                        ]
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
                                        ]
                                    ],
                                ]
                            ],
                        ]
                    ],
                    RolesEnum::HealthManager => [
                        'teams' => [
                            TeamNameEnum::RM_NB => [
                                'quote_status' => [
                                    QuoteStatusEnum::FollowedUp => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                        ]
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 2],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                        ]
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
                                        ]
                                    ],
                                ]
                            ],
                            TeamNameEnum::RM_SPEED => [
                                'quote_status' => [
                                    QuoteStatusEnum::FollowedUp => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                        ]
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 2],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                        ]
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
                                        ]
                                    ],
                                ]
                            ],
                            TeamNameEnum::EBP => [
                                'quote_status' => [
                                    QuoteStatusEnum::FollowedUp => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                        ]
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 2],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                        ]
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
                                        ]
                                    ],
                                ]
                            ],
                            TeamNameEnum::RM_RENEWALS => [
                                'quote_status' => [
                                    QuoteStatusEnum::RenewalTermsReceived => [
                                        'activities' => [
                                            ['name' => 'Email renewal terms', 'due_days' => 2]
                                        ]
                                    ],
                                    QuoteStatusEnum::Quoted => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 2],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Call Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 3],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                            ['name' => '3rd Call Follow-up', 'due_days' => 3],
                                        ]
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            ['name' => '1st Call Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Call Follow-up', 'due_days' => 3],
                                            ['name' => '3rd Call Follow-up', 'due_days' => 5],
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                ]
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
                                        ]
                                    ],
                                    QuoteStatusEnum::AdditionalInformationRequested => [
                                        'activities' => [
                                            ['name' => 'Additional Information 1st Follow-up', 'due_days' => 1],
                                            ['name' => 'Additional Information 2nd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::QuoteRequested => [
                                        'activities' => [
                                            ['name' => 'Follow-up Quotes 1', 'due_days' => 2],
                                            ['name' => 'Follow-up Quotes 2', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::Quoted => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::FinalizingTerms => [
                                        'activities' => [
                                            ['name' => 'Finalizing Terms 1st Follow-up', 'due_days' => 2],
                                            ['name' => 'Finalizing Terms 2nd Follow-up', 'due_days' => 2],
                                        ]
                                    ]
                                ]
                            ],
                            TeamNameEnum::RENEWALS => [
                                'quote_status' => [
                                    QuoteStatusEnum::FollowedUp => [
                                        'activities' => [
                                            ['name' => '1st Reminder', 'due_days' => 1],
                                            ['name' => '2nd Reminder', 'due_days' => 2],
                                            ['name' => '3rd Reminder', 'due_days' => 4],
                                            ['name' => '4th Reminder', 'due_days' => 13],
                                        ]
                                    ],
                                    QuoteStatusEnum::PendingRenewalInformation => [
                                        'activities' => [
                                            ['name' => 'Pending Renewal Information Reminder 1', 'due_days' => 2],
                                            ['name' => 'Pending Renewal Information Reminder 2', 'due_days' => 2],
                                            ['name' => 'Pending Renewal Information Reminder 3', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::QuoteRequested => [
                                        'activities' => [
                                            ['name' => 'Follow-up Quotes 1', 'due_days' => 2],
                                            ['name' => 'Follow-up Quotes 2', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::Quoted => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::FinalizingTerms => [
                                        'activities' => [
                                            ['name' => 'Finalizing Terms 1st Follow-up', 'due_days' => 2],
                                            ['name' => 'Finalizing Terms 2nd Follow-up', 'due_days' => 2],
                                            ['name' => 'Finalizing Terms 3rd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                ]
                            ]
                        ]
                    ],
                    RolesEnum::CorpLineAdvisor => [
                        'teams' => [
                            TeamNameEnum::CORPLINE_TEAM => [
                                'quote_status' => [
                                    QuoteStatusEnum::AdditionalInformationRequested => [
                                        'activities' => [
                                            ['name' => 'Additional Information 1st Follow-up', 'due_days' => 1],
                                            ['name' => 'Additional Information 2nd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::Quoted => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::FinalizingTerms => [
                                        'activities' => [
                                            ['name' => 'Finalizing Terms 1st Follow-up', 'due_days' => 2],
                                            ['name' => 'Finalizing Terms 2nd Follow-up', 'due_days' => 2],
                                        ]
                                    ]
                                ]
                            ],
                            TeamNameEnum::RENEWALS => [
                                'quote_status' => [
                                    QuoteStatusEnum::FollowedUp => [
                                        'activities' => [
                                            ['name' => '1st Reminder', 'due_days' => 1],
                                            ['name' => '2nd Reminder', 'due_days' => 2],
                                            ['name' => '3rd Reminder', 'due_days' => 4],
                                            ['name' => '4th Reminder', 'due_days' => 13],
                                        ]
                                    ],
                                    QuoteStatusEnum::PendingRenewalInformation => [
                                        'activities' => [
                                            ['name' => 'Pending Renewal Information Reminder 1', 'due_days' => 2],
                                            ['name' => 'Pending Renewal Information Reminder 2', 'due_days' => 2],
                                            ['name' => 'Pending Renewal Information Reminder 3', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::QuoteRequested => [
                                        'activities' => [
                                            ['name' => 'Follow-up Quotes 1', 'due_days' => 2],
                                            ['name' => 'Follow-up Quotes 2', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::Quoted => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::FinalizingTerms => [
                                        'activities' => [
                                            ['name' => 'Finalizing Terms 1st Follow-up', 'due_days' => 2],
                                            ['name' => 'Finalizing Terms 2nd Follow-up', 'due_days' => 2],
                                            ['name' => 'Finalizing Terms 3rd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                ]
                            ]
                        ]
                    ],
                ]
            ],
            [
                'quote_type_id' => QuoteTypeId::Home,
                'roles' => [
                    RolesEnum::HomeManager => [
                        'teams' => [
                            TeamNameEnum::NO_TEAM => [ // It should be Home New Business Sub Team
                                'quote_status' => [
                                    QuoteStatusEnum::Allocated => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::Quoted => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 2],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                ]
                            ],
                            TeamNameEnum::RENEWALS => [ // It should be Home Renewals Sub Team
                                'quote_status' => [
                                    QuoteStatusEnum::Allocated => [
                                        'activities' => [
                                            ['name' => 'Follow-up', 'due_days' => 1],
                                        ]
                                    ],
                                    QuoteStatusEnum::RenewalTermsSent => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                ]
                            ]
                        ]
                    ],
                    RolesEnum::HomeAdvisor => [
                        'teams' => [
                            TeamNameEnum::NO_TEAM => [ // It should be Home New Business Sub Team
                                'quote_status' => [
                                    QuoteStatusEnum::Quoted => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 2],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                ]
                            ],
                            TeamNameEnum::RENEWALS => [ // It should be Home Renewals Sub Team
                                'quote_status' => [
                                    QuoteStatusEnum::Allocated => [
                                        'activities' => [
                                            ['name' => 'Follow-up', 'due_days' => 1],
                                        ]
                                    ],
                                    QuoteStatusEnum::RenewalTermsSent => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                ]
                            ]
                        ]
                    ],
                ]
            ],
            [
                'quote_type_id' => QuoteTypeId::Pet,
                'roles' => [
                    RolesEnum::PetManager => [
                        'teams' => [
                            TeamNameEnum::NO_TEAM => [ // It should be Pet New Business Sub Team
                                'quote_status' => [
                                    QuoteStatusEnum::Allocated => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::Quoted => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 2],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                ]
                            ],
                            TeamNameEnum::RENEWALS => [ // It should be Pet Renewals Sub Team
                                'quote_status' => [
                                    QuoteStatusEnum::Allocated => [
                                        'activities' => [
                                            ['name' => 'Follow-up', 'due_days' => 1],
                                        ]
                                    ],
                                    QuoteStatusEnum::RenewalTermsSent => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                ]
                            ]
                        ]
                    ],
                    RolesEnum::PetAdvisor => [
                        'teams' => [
                            TeamNameEnum::NO_TEAM => [ // It should be Pet New Business Sub Team
                                'quote_status' => [
                                    QuoteStatusEnum::Quoted => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 2],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                ]
                            ],
                            TeamNameEnum::RENEWALS => [ // It should be Pet Renewals Sub Team
                                'quote_status' => [
                                    QuoteStatusEnum::Allocated => [
                                        'activities' => [
                                            ['name' => 'Follow-up', 'due_days' => 1],
                                        ]
                                    ],
                                    QuoteStatusEnum::RenewalTermsSent => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                ]
                            ]
                        ]
                    ],
                ]
            ],
            [
                'quote_type_id' => QuoteTypeId::Cycle,
                'roles' => [
                    RolesEnum::CycleManager => [
                        'teams' => [
                            TeamNameEnum::NO_TEAM => [ // It should be Cycle New Business Sub Team
                                'quote_status' => [
                                    QuoteStatusEnum::Allocated => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::Quoted => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 2],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                ]
                            ],
                            TeamNameEnum::RENEWALS => [ // It should be Cycle Renewals Sub Team
                                'quote_status' => [
                                    QuoteStatusEnum::Allocated => [
                                        'activities' => [
                                            ['name' => 'Follow-up', 'due_days' => 1],
                                        ]
                                    ],
                                    QuoteStatusEnum::RenewalTermsSent => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                ]
                            ]
                        ]
                    ],
                    RolesEnum::CycleAdvisor => [
                        'teams' => [
                            TeamNameEnum::NO_TEAM => [ // It should be Cycle New Business Sub Team
                                'quote_status' => [
                                    QuoteStatusEnum::Quoted => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 2],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                ]
                            ],
                            TeamNameEnum::RENEWALS => [ // It should be Cycle Renewals Sub Team
                                'quote_status' => [
                                    QuoteStatusEnum::Allocated => [
                                        'activities' => [
                                            ['name' => 'Follow-up', 'due_days' => 1],
                                        ]
                                    ],
                                    QuoteStatusEnum::RenewalTermsSent => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                ]
                            ]
                        ]
                    ],
                ]
            ],
            [
                'quote_type_id' => QuoteTypeId::Yacht,
                'roles' => [
                    RolesEnum::YachtManager => [
                        'teams' => [
                            TeamNameEnum::NO_TEAM => [ // It should be Yacht New Business Sub Team
                                'quote_status' => [
                                    QuoteStatusEnum::Allocated => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::Quoted => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 2],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                ]
                            ],
                            TeamNameEnum::RENEWALS => [ // It should be Yacht Renewals Sub Team
                                'quote_status' => [
                                    QuoteStatusEnum::Allocated => [
                                        'activities' => [
                                            ['name' => 'Follow-up', 'due_days' => 1],
                                        ]
                                    ],
                                    QuoteStatusEnum::RenewalTermsSent => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                ]
                            ]
                        ]
                    ],
                    RolesEnum::YachtAdvisor => [
                        'teams' => [
                            TeamNameEnum::NO_TEAM => [ // It should be Yacht New Business Sub Team
                                'quote_status' => [
                                    QuoteStatusEnum::Quoted => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::InNegotiation => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 2],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                ]
                            ],
                            TeamNameEnum::RENEWALS => [ // It should be Yacht Renewals Sub Team
                                'quote_status' => [
                                    QuoteStatusEnum::Allocated => [
                                        'activities' => [
                                            ['name' => 'Follow-up', 'due_days' => 1],
                                        ]
                                    ],
                                    QuoteStatusEnum::RenewalTermsSent => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                            ['name' => '3rd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                    QuoteStatusEnum::PaymentPending => [
                                        'activities' => [
                                            ['name' => '1st Follow-up', 'due_days' => 1],
                                            ['name' => '2nd Follow-up', 'due_days' => 2],
                                        ]
                                    ],
                                ]
                            ]
                        ]
                    ],
                ]
            ]
        ];

        $finalSchedules = [];
        foreach ($schedules as $schedule) {
            foreach ($schedule['roles'] as $roleKey => $role) {
                foreach ($role['teams'] as $teamKey => $team) {
                    foreach ($team['quote_status'] as $quoteStatusKey => $quoteStatus) {
                        foreach ($quoteStatus['activities'] as $key => $activity) {
                            $finalSchedules[] = [
                                'quote_type_id' => $schedule['quote_type_id'],
                                'role_id' => $rolesArray[$roleKey],
                                'team_id' => $teamsArray[$teamKey],
                                'quote_status_id' => $quoteStatusKey,
                                'name' => $activity['name'],
                                'description' => $activity['name'],
                                'sorting_order' => ++$key,
                                'due_days' => $activity['due_days'],
                            ];
                        }
                    }
                }
            }
        }

        // Inserting all the schedules
        // Need to check if the schedule already exists
        // ActivitySchedule::insert($finalSchedules);
    }
}
