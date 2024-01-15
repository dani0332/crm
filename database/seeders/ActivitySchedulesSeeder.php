<?php

namespace Database\Seeders;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
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

        // Todo:: Need to confirm roles and SubTeams as per production
        $rolesArray = [];
        $rolesUsedInActivities = [
            RolesEnum::HealthNewBusinessAdvisor,
            RolesEnum::HealthRenewalAdvisor,
            RolesEnum::CorplineSalesCoordinator,
            RolesEnum::CorpLineAdvisor,
            RolesEnum::OE,
            RolesEnum::HomeSalesCoordinator,
            RolesEnum::HomeAdvisor,
            RolesEnum::PetSalesCoordinator,
            RolesEnum::PetAdvisor,
            RolesEnum::CycleSalesCoordinator,
            RolesEnum::CycleAdvisor,
        ];

        foreach($rolesUsedInActivities as $role) {
            $rolesArray[$role] = Role::where('name', $role)->first()->id ?? null;
        }

        $teamsArray = [];
        $teamsUsedInActivities = [
            TeamNameEnum::ORGANIC,
            TeamNameEnum::RENEWALS,
            // TeamNameEnum::NewBusiness,
        ];

        foreach($teamsUsedInActivities as $team) {
            $teamsArray[$team] = Team::where('name', $team)->first()->id ?? null;

            // if (empty($teamsArray[$team])) {
            //     $teamsArray[$team] = Team::create([
            //         'name' => $team,
            //         'description' => $team,
            //     ])->id;
            // }
        }


        $schedules = [
            [
                'quote_type_id' => QuoteTypeId::Health,
                'roles' => [
                    RolesEnum::HealthNewBusinessAdvisor => [
                        'teams' => [
                            // TeamNameEnum::NewBusiness => [
                            TeamNameEnum::RENEWALS => [
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
                            ]
                        ]
                    ],
                    RolesEnum::HealthRenewalAdvisor => [
                        'teams' => [
                            TeamNameEnum::RENEWALS => [
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
                    ]
                ]
            ],
            // [
            //     'quote_type_id' => QuoteTypeId::Business,
            //     'roles' => [
            //         RolesEnum::CorplineSalesCoordinator => [
            //             'teams' => [
            //                 TeamNameEnum::NewBusiness => [
            //                     'quote_status' => [
            //                         QuoteStatusEnum::ProposalFormRequested => [
            //                             'activities' => [
            //                                 ['name' => '1st Follow-up', 'due_days' => 1],
            //                                 ['name' => '2nd Follow-up', 'due_days' => 2],
            //                                 ['name' => '3rd Follow-up', 'due_days' => 2],
            //                             ]
            //                         ],
            //                         QuoteStatusEnum::AdditionalInformationRequested => [
            //                             'activities' => [
            //                                 ['name' => 'Additional Information 1st Follow-up', 'due_days' => 1],
            //                                 ['name' => 'Additional Information 2nd Follow-up', 'due_days' => 2],
            //                             ]
            //                         ],
            //                         QuoteStatusEnum::Quoted => [
            //                             'activities' => [
            //                                 ['name' => '1st Follow-up', 'due_days' => 1],
            //                                 ['name' => '2nd Follow-up', 'due_days' => 2],
            //                                 ['name' => '3rd Follow-up', 'due_days' => 2],
            //                             ]
            //                         ],
            //                         QuoteStatusEnum::FinalizingTerms => [
            //                             'activities' => [
            //                                 ['name' => 'Finalizing Terms 1st Follow-up', 'due_days' => 2],
            //                                 ['name' => 'Finalizing Terms 2nd Follow-up', 'due_days' => 2],
            //                             ]
            //                         ]
            //                     ]
            //                 ]
            //             ]
            //         ],
            //         RolesEnum::CorpLineAdvisor => [
            //             'teams' => [
            //                 TeamNameEnum::NewBusiness => [
            //                     'quote_status_id' => [
            //                         QuoteStatusEnum::AdditionalInformationRequested => [
            //                             'activities' => [
            //                                 ['name' => 'Additional Information 1st Follow-up', 'due_days' => 1],
            //                                 ['name' => 'Additional Information 2nd Follow-up', 'due_days' => 2],
            //                             ]
            //                         ],
            //                         QuoteStatusEnum::Quoted => [
            //                             'activities' => [
            //                                 ['name' => '1st Follow-up', 'due_days' => 1],
            //                                 ['name' => '2nd Follow-up', 'due_days' => 2],
            //                                 ['name' => '3rd Follow-up', 'due_days' => 2],
            //                             ]
            //                         ],
            //                         QuoteStatusEnum::FinalizingTerms => [
            //                             'activities' => [
            //                                 ['name' => 'Finalizing Terms 1st Follow-up', 'due_days' => 2],
            //                                 ['name' => 'Finalizing Terms 2nd Follow-up', 'due_days' => 2],
            //                             ]
            //                         ]
            //                     ]
            //                 ],
            //                 TeamNameEnum::RENEWALS => [
            //                     'quote_status_id' => [
            //                         QuoteStatusEnum::FollowedUp => [
            //                             'activities' => [
            //                                 ['name' => '1st Reminder', 'due_days' => 1],
            //                                 ['name' => '2nd Reminder', 'due_days' => 2],
            //                                 ['name' => '3rd Reminder', 'due_days' => 4],
            //                                 ['name' => '4th Reminder', 'due_days' => 13],
            //                             ]
            //                         ],
            //                         QuoteStatusEnum::PendingRenewalInformation => [
            //                             'activities' => [
            //                                 ['name' => 'Pending Renewal Information Reminder 1', 'due_days' => 2],
            //                                 ['name' => 'Pending Renewal Information Reminder 2', 'due_days' => 2],
            //                                 ['name' => 'Pending Renewal Information Reminder 3', 'due_days' => 2],
            //                             ]
            //                         ],
            //                         QuoteStatusEnum::Quoted => [
            //                             'activities' => [
            //                                 ['name' => '1st Follow-up', 'due_days' => 1],
            //                                 ['name' => '2nd Follow-up', 'due_days' => 2],
            //                                 ['name' => '3rd Follow-up', 'due_days' => 2],
            //                             ]
            //                         ],
            //                         QuoteStatusEnum::FinalizingTerms => [
            //                             'activities' => [
            //                                 ['name' => 'Finalizing Terms 1st Follow-up', 'due_days' => 2],
            //                                 ['name' => 'Finalizing Terms 2nd Follow-up', 'due_days' => 2],
            //                                 ['name' => 'Finalizing Terms 3rd Follow-up', 'due_days' => 2],
            //                             ]
            //                         ],
            //                     ]
            //                 ]
            //             ]
            //         ],
            //         RolesEnum::OE => [
            //             'teams' => [
            //                 TeamNameEnum::NewBusiness => [
            //                     'quote_status_id' => [
            //                         QuoteStatusEnum::QuoteRequested => [
            //                             'activities' => [
            //                                 ['name' => 'Follow-up Quotes 1', 'due_days' => 2],
            //                                 ['name' => 'Follow-up Quotes 2', 'due_days' => 2],
            //                             ]
            //                         ],
            //                     ]
            //                 ],
            //                 TeamNameEnum::RENEWALS => [
            //                     'quote_status_id' => [
            //                         QuoteStatusEnum::QuoteRequested => [
            //                             'activities' => [
            //                                 ['name' => 'Follow-up Quotes 1', 'due_days' => 2],
            //                                 ['name' => 'Follow-up Quotes 2', 'due_days' => 2],
            //                             ]
            //                         ]
            //                     ]
            //                 ]
            //             ]
            //         ],
            //     ]
            // ],
            // [
            //     'quote_type_id' => QuoteTypeId::Home,
            //     'roles' => [
            //         RolesEnum::HomeSalesCoordinator => [
            //             'teams' => [
            //                 TeamNameEnum::NewBusiness => [
            //                     'quote_status' => [
            //                         QuoteStatusEnum::Allocated => [
            //                             'activities' => [
            //                                 ['name' => '1st Follow-up', 'due_days' => 1],
            //                                 ['name' => '2nd Follow-up', 'due_days' => 2],
            //                                 ['name' => '3rd Follow-up', 'due_days' => 2],
            //                             ]
            //                         ],
            //                     ]
            //                 ],
            //             ]
            //         ],
            //         RolesEnum::HomeAdvisor => [
            //             'teams' => [
            //                 TeamNameEnum::NewBusiness => [
            //                     'quote_status' => [
            //                         QuoteStatusEnum::Quoted => [
            //                             'activities' => [
            //                                 ['name' => '1st Follow-up', 'due_days' => 1],
            //                                 ['name' => '2nd Follow-up', 'due_days' => 2],
            //                                 ['name' => '3rd Follow-up', 'due_days' => 2],
            //                             ]
            //                         ],
            //                         QuoteStatusEnum::InNegotiation => [
            //                             'activities' => [
            //                                 ['name' => '1st Follow-up', 'due_days' => 2],
            //                                 ['name' => '2nd Follow-up', 'due_days' => 2],
            //                             ]
            //                         ],
            //                         QuoteStatusEnum::PaymentPending => [
            //                             'activities' => [
            //                                 ['name' => '1st Follow-up', 'due_days' => 1],
            //                                 ['name' => '2nd Follow-up', 'due_days' => 2],
            //                             ]
            //                         ],
            //                     ]
            //                 ],
            //                 TeamNameEnum::RENEWALS => [
            //                     'quote_status' => [
            //                         QuoteStatusEnum::Allocated => [
            //                             'activities' => [
            //                                 ['name' => 'Follow-up', 'due_days' => 1],
            //                             ]
            //                         ],
            //                         QuoteStatusEnum::RenewalTermsSent => [
            //                             'activities' => [
            //                                 ['name' => '1st Follow-up', 'due_days' => 1],
            //                                 ['name' => '2nd Follow-up', 'due_days' => 2],
            //                                 ['name' => '3rd Follow-up', 'due_days' => 2],
            //                             ]
            //                         ],
            //                         QuoteStatusEnum::PaymentPending => [
            //                             'activities' => [
            //                                 ['name' => '1st Follow-up', 'due_days' => 1],
            //                                 ['name' => '2nd Follow-up', 'due_days' => 2],
            //                             ]
            //                         ],
            //                     ]
            //                 ]
            //             ]
            //         ],
            //     ]
            // ],
            // [
            //     'quote_type_id' => QuoteTypeId::Pet,
            //     'roles' => [
            //         RolesEnum::PetSalesCoordinator => [
            //             'teams' => [
            //                 TeamNameEnum::NewBusiness => [
            //                     'quote_status' => [
            //                         QuoteStatusEnum::Allocated => [
            //                             'activities' => [
            //                                 ['name' => '1st Follow-up', 'due_days' => 1],
            //                                 ['name' => '2nd Follow-up', 'due_days' => 2],
            //                                 ['name' => '3rd Follow-up', 'due_days' => 2],
            //                             ]
            //                         ],
            //                     ]
            //                 ],
            //             ]
            //         ],
            //         RolesEnum::PetAdvisor => [
            //             'teams' => [
            //                 TeamNameEnum::NewBusiness => [
            //                     'quote_status' => [
            //                         QuoteStatusEnum::Quoted => [
            //                             'activities' => [
            //                                 ['name' => '1st Follow-up', 'due_days' => 1],
            //                                 ['name' => '2nd Follow-up', 'due_days' => 2],
            //                                 ['name' => '3rd Follow-up', 'due_days' => 2],
            //                             ]
            //                         ],
            //                         QuoteStatusEnum::InNegotiation => [
            //                             'activities' => [
            //                                 ['name' => '1st Follow-up', 'due_days' => 2],
            //                                 ['name' => '2nd Follow-up', 'due_days' => 2],
            //                             ]
            //                         ],
            //                         QuoteStatusEnum::PaymentPending => [
            //                             'activities' => [
            //                                 ['name' => '1st Follow-up', 'due_days' => 1],
            //                                 ['name' => '2nd Follow-up', 'due_days' => 2],
            //                             ]
            //                         ],
            //                     ]
            //                 ],
            //                 TeamNameEnum::RENEWALS => [
            //                     'quote_status' => [
            //                         QuoteStatusEnum::Allocated => [
            //                             'activities' => [
            //                                 ['name' => 'Follow-up', 'due_days' => 1],
            //                             ]
            //                         ],
            //                         QuoteStatusEnum::RenewalTermsSent => [
            //                             'activities' => [
            //                                 ['name' => '1st Follow-up', 'due_days' => 1],
            //                                 ['name' => '2nd Follow-up', 'due_days' => 2],
            //                                 ['name' => '3rd Follow-up', 'due_days' => 2],
            //                             ]
            //                         ],
            //                         QuoteStatusEnum::PaymentPending => [
            //                             'activities' => [
            //                                 ['name' => '1st Follow-up', 'due_days' => 1],
            //                                 ['name' => '2nd Follow-up', 'due_days' => 2],
            //                             ]
            //                         ],
            //                     ]
            //                 ]
            //             ]
            //         ],
            //     ]
            // ],
            // [
            //     'quote_type_id' => QuoteTypeId::Cycle,
            //     'roles' => [
            //         RolesEnum::CycleSalesCoordinator => [
            //             'teams' => [
            //                 TeamNameEnum::NewBusiness => [
            //                     'quote_status' => [
            //                         QuoteStatusEnum::Allocated => [
            //                             'activities' => [
            //                                 ['name' => '1st Follow-up', 'due_days' => 1],
            //                                 ['name' => '2nd Follow-up', 'due_days' => 2],
            //                                 ['name' => '3rd Follow-up', 'due_days' => 2],
            //                             ]
            //                         ],
            //                     ]
            //                 ],
            //             ]
            //         ],
            //         RolesEnum::CycleAdvisor => [
            //             'teams' => [
            //                 TeamNameEnum::NewBusiness => [
            //                     'quote_status' => [
            //                         QuoteStatusEnum::Quoted => [
            //                             'activities' => [
            //                                 ['name' => '1st Follow-up', 'due_days' => 1],
            //                                 ['name' => '2nd Follow-up', 'due_days' => 2],
            //                                 ['name' => '3rd Follow-up', 'due_days' => 2],
            //                             ]
            //                         ],
            //                         QuoteStatusEnum::InNegotiation => [
            //                             'activities' => [
            //                                 ['name' => '1st Follow-up', 'due_days' => 2],
            //                                 ['name' => '2nd Follow-up', 'due_days' => 2],
            //                             ]
            //                         ],
            //                         QuoteStatusEnum::PaymentPending => [
            //                             'activities' => [
            //                                 ['name' => '1st Follow-up', 'due_days' => 1],
            //                                 ['name' => '2nd Follow-up', 'due_days' => 2],
            //                             ]
            //                         ],
            //                     ]
            //                 ],
            //                 TeamNameEnum::RENEWALS => [
            //                     'quote_status' => [
            //                         QuoteStatusEnum::Allocated => [
            //                             'activities' => [
            //                                 ['name' => 'Follow-up', 'due_days' => 1],
            //                             ]
            //                         ],
            //                         QuoteStatusEnum::RenewalTermsSent => [
            //                             'activities' => [
            //                                 ['name' => '1st Follow-up', 'due_days' => 1],
            //                                 ['name' => '2nd Follow-up', 'due_days' => 2],
            //                                 ['name' => '3rd Follow-up', 'due_days' => 2],
            //                             ]
            //                         ],
            //                         QuoteStatusEnum::PaymentPending => [
            //                             'activities' => [
            //                                 ['name' => '1st Follow-up', 'due_days' => 1],
            //                                 ['name' => '2nd Follow-up', 'due_days' => 2],
            //                             ]
            //                         ],
            //                     ]
            //                 ]
            //             ]
            //         ],
            //     ]
            // ]
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
        ActivitySchedule::insert($finalSchedules);
    }
}
