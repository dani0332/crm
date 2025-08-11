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
        // Get role and team IDs
        $gmAdvisorRole = Role::where('name', RolesEnum::GMAdvisor)->first();
        $gmManagerRole = Role::where('name', RolesEnum::GMManager)->first();

        // Get teams for Group Medical - using multiple teams as shown in screenshot
        $entryLevelTeam = Team::where('name', TeamNameEnum::EBP)->first(); // Entry-Level
        $goodTeam = Team::where('name', TeamNameEnum::RM_SPEED)->first(); // Good
        $bestTeam = Team::where('name', TeamNameEnum::RM_NB)->first(); // Best
        $renewalTeam = Team::where('name', TeamNameEnum::RM_RENEWALS)->first(); // RM-Renewals

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
                        // ['name' => '1st Call Follow-up', 'due_days' => 1],
                        // ['name' => '2nd Call Follow-up', 'due_days' => 3],

                        // for testing purpose
                        ['name' => '1st Call Follow-up', 'due_days' => 1],
                        ['name' => '2nd Call Follow-up', 'due_days' => 1],
                    ],
                    QuoteStatusEnum::InNegotiation => [
                        // ['name' => '1st Call Follow-up', 'due_days' => 2],
                        // ['name' => '2nd Call Follow-up', 'due_days' => 3],

                        // for testing purpose
                        ['name' => '1st Call Follow-up', 'due_days' => 1],
                        ['name' => '2nd Call Follow-up', 'due_days' => 1],
                    ],
                    QuoteStatusEnum::ApplicationPending => [
                        // ['name' => '1st Call Follow-up', 'due_days' => 3],
                        // ['name' => '2nd Call Follow-up', 'due_days' => 3],
                        // ['name' => '3rd Call Follow-up', 'due_days' => 3],

                        // for testing purpose
                        ['name' => '1st Call Follow-up', 'due_days' => 1],
                        ['name' => '2nd Call Follow-up', 'due_days' => 1],
                        ['name' => '3rd Call Follow-up', 'due_days' => 1],
                    ],
                    QuoteStatusEnum::PaymentPending => [
                        // ['name' => '1st Call Follow-up', 'due_days' => 1],
                        // ['name' => '2nd Call Follow-up', 'due_days' => 3],
                        // ['name' => '3rd Call Follow-up', 'due_days' => 5],

                        // for testing purpose
                        ['name' => '1st Call Follow-up', 'due_days' => 1],
                        ['name' => '2nd Call Follow-up', 'due_days' => 1],
                        ['name' => '3rd Call Follow-up', 'due_days' => 1],
                    ],
                ],
            ],

            // RENEWAL ACTIVITIES - RM-Renewals team
            [
                'teams' => [$renewalTeam],
                'roles' => [$gmAdvisorRole, $gmManagerRole],
                'statuses_activities' => [
                    QuoteStatusEnum::Allocated => [
                        // ['name' => '1st Call Follow-up', 'due_days' => 2],
                        // ['name' => '2nd Call Follow-up', 'due_days' => 2],

                        // for testing purpose
                        ['name' => '1st Call Follow-up', 'due_days' => 1],
                        ['name' => '2nd Call Follow-up', 'due_days' => 1],
                    ],
                    QuoteStatusEnum::RenewalTermsSent => [
                        // ['name' => 'Email renewal terms', 'due_days' => 2],

                        // for testing purpose
                        ['name' => 'Email renewal terms', 'due_days' => 1],
                    ],
                    QuoteStatusEnum::Quoted => [
                        // ['name' => '1st Call Follow-up', 'due_days' => 2],
                        // ['name' => '2nd Call Follow-up', 'due_days' => 2],
                        // ['name' => '3rd Call Follow-up', 'due_days' => 2],

                        // for testing purpose
                        ['name' => '1st Call Follow-up', 'due_days' => 1],
                        ['name' => '2nd Call Follow-up', 'due_days' => 1],
                        ['name' => '3rd Call Follow-up', 'due_days' => 1],
                    ],
                    QuoteStatusEnum::InNegotiation => [
                        // ['name' => '1st Call Follow-up', 'due_days' => 3],
                        // ['name' => '2nd Call Follow-up', 'due_days' => 3],
                        // ['name' => '3rd Call Follow-up', 'due_days' => 3],

                        // for testing purpose
                        ['name' => '1st Call Follow-up', 'due_days' => 1],
                        ['name' => '2nd Call Follow-up', 'due_days' => 1],
                        ['name' => '3rd Call Follow-up', 'due_days' => 1],
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
