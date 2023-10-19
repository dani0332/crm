<?php

namespace App\Console\Commands;

use App\Enums\TeamNameEnum;
use App\Models\LeadAllocation;
use App\Models\Team;
use App\Models\UserTeams;
use Illuminate\Console\Command;

class ResetLeadAllocationCounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ResetLeadAllocationCounts:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset users allocation count every night';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        info('Scheduler is about to reset counts for every user');

        // Reset counts for all users
        LeadAllocation::query()->update([
            'allocation_count' => 0,
            'auto_assignment_count' => 0,
            'manual_assignment_count' => 0,
        ]);

        info('Scheduler has reset counts for every user');

        // Get the PCP team
        $pcpTeam = Team::query()->where('name', TeamNameEnum::PCP)->first();

        if ($pcpTeam) {
            // Get the user IDs belonging to the PCP team
            $pcpUserIds = UserTeams::query()->where('team_id', $pcpTeam->id)->pluck('user_id')->toArray();

            // Reset max capacity for all users except those in the PCP team
            LeadAllocation::query()->whereNotIn('user_id', $pcpUserIds)->update([
                'max_capacity' => 50,
            ]);

            info('Scheduler has reset max capacity for all users except PCP team members');
        } else {
            // Reset max capacity for all users
            LeadAllocation::query()->update([
                'max_capacity' => 50,
            ]);

            info('Scheduler has reset max capacity for all users');
        }

        return 0;
    }

}
