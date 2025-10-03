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

class GroupMedicalActivityScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Fetch all required roles in a single query
        $roles = Role::whereIn('name', [
            RolesEnum::GMAdvisor,
            RolesEnum::GMManager,
        ])->get()->keyBy('name');

        // Fetch all required teams in a single query
        $teams = Team::whereIn('name', [
            TeamNameEnum::EBP,
            TeamNameEnum::RM_SPEED,
            TeamNameEnum::RM_NB,
            TeamNameEnum::RM_RENEWALS,
        ])->get()->keyBy('name');

        // Filter and assign roles to respective variables
        $gmAdvisorRole = $roles->get(RolesEnum::GMAdvisor);
        $gmManagerRole = $roles->get(RolesEnum::GMManager);

        // Filter and assign teams to respective variables
        $entryLevelTeam = $teams->get(TeamNameEnum::EBP); // Entry-Level
        $goodTeam = $teams->get(TeamNameEnum::RM_SPEED); // Good
        $bestTeam = $teams->get(TeamNameEnum::RM_NB); // Best
        $renewalTeam = $teams->get(TeamNameEnum::RM_RENEWALS); // RM-Renewals

        if (! $gmAdvisorRole || ! $gmManagerRole) {
            $this->command->error('Required GM roles not found. Please ensure GM_ADVISOR and GM_MANAGER roles exist.');

            return;
        }

        if (! $entryLevelTeam || ! $goodTeam || ! $bestTeam || ! $renewalTeam) {
            $this->command->error('Required teams not found. Please ensure Entry-Level, Good, Best, and RM-Renewals teams exist.');

            return;
        }

        // Define Group Medical activity schedules based on the screenshot
        $schedules = [
            // NEW BUSINESS ACTIVITIES - Multiple categories (Entry level, Good, Best)
            [
                'teams' => [$entryLevelTeam, $goodTeam, $bestTeam],
                'roles' => [$gmAdvisorRole, $gmManagerRole],
                'statuses_activities' => [
                    QuoteStatusEnum::FollowedUp => [
                        ['name' => '1st Call Follow-up', 'due_days' => 1],
                        ['name' => '2nd Call Follow-up', 'due_days' => 3],
                    ],
                    QuoteStatusEnum::InNegotiation => [
                        ['name' => '1st Call Follow-up', 'due_days' => 2],
                        ['name' => '2nd Call Follow-up', 'due_days' => 3],

                    ],
                    QuoteStatusEnum::ApplicationPending => [
                        ['name' => '1st Call Follow-up', 'due_days' => 3],
                        ['name' => '2nd Call Follow-up', 'due_days' => 3],
                        ['name' => '3rd Call Follow-up', 'due_days' => 3],

                    ],
                    QuoteStatusEnum::PaymentPending => [
                        ['name' => '1st Call Follow-up', 'due_days' => 1],
                        ['name' => '2nd Call Follow-up', 'due_days' => 3],
                        ['name' => '3rd Call Follow-up', 'due_days' => 5],

                    ],
                ],
            ],

            // RENEWAL ACTIVITIES - RM-Renewals team
            [
                'teams' => [$renewalTeam],
                'roles' => [$gmAdvisorRole, $gmManagerRole],
                'statuses_activities' => [
                    QuoteStatusEnum::Allocated => [
                        ['name' => '1st Call Follow-up', 'due_days' => 2],
                        ['name' => '2nd Call Follow-up', 'due_days' => 2],

                    ],
                    QuoteStatusEnum::RenewalTermsReceived => [
                        ['name' => 'Email renewal terms', 'due_days' => 2],

                    ],
                    QuoteStatusEnum::Quoted => [
                        ['name' => '1st Call Follow-up', 'due_days' => 2],
                        ['name' => '2nd Call Follow-up', 'due_days' => 2],
                        ['name' => '3rd Call Follow-up', 'due_days' => 2],

                    ],
                    QuoteStatusEnum::InNegotiation => [
                        ['name' => '1st Call Follow-up', 'due_days' => 3],
                        ['name' => '2nd Call Follow-up', 'due_days' => 3],
                        ['name' => '3rd Call Follow-up', 'due_days' => 3],

                    ],
                    QuoteStatusEnum::PaymentPending => [
                        ['name' => '1st Call Follow-up', 'due_days' => 1],
                    ],
                ],
            ],
        ];

        // Create activity schedules
        foreach ($schedules as $schedule) {
            foreach ($schedule['teams'] as $team) {
                foreach ($schedule['roles'] as $role) {
                    foreach ($schedule['statuses_activities'] as $statusId => $activities) {
                        $sortingOrder = 1;
                        foreach ($activities as $activity) {
                            ActivitySchedule::firstOrCreate([
                                'quote_type_id' => QuoteTypeId::Business,
                                'quote_status_id' => $statusId,
                                'role_id' => $role->id,
                                'team_id' => $team->id,
                                'name' => $activity['name'],
                                'sorting_order' => $sortingOrder,
                            ], [
                                'description' => $activity['name'],
                                'due_days' => $activity['due_days'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            $sortingOrder++;
                        }
                    }
                }
            }
        }

        $this->command->info('Group Medical Activity Schedules have been seeded successfully.');
    }
}
