<?php

namespace App\Console\Commands;

use App\Enums\UserStatusEnum;
use App\Models\LeadAllocation;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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

        LeadAllocation::query()->where('reset_cap', 1)->update([
            'allocation_count' => 0,
            'auto_assignment_count' => 0,
            'manual_assignment_count' => 0,
            'max_capacity' => 20,
        ]);

        LeadAllocation::query()->where('reset_cap', 0)->update([
            'allocation_count' => 0,
            'auto_assignment_count' => 0,
            'manual_assignment_count' => 0,
        ]);

        DB::table('sessions')->delete(); // truncate sessions table

        User::query()->where('is_active', 1)
            ->whereNotIn('status', [UserStatusEnum::LEAVE, UserStatusEnum::SICK])
            ->update([
                'status' => UserStatusEnum::UNAVAILABLE,
            ]);

        info('Scheduler has reset counts for all users where reset_cap was true');

        return 0;
    }
}
