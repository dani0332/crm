<?php

namespace App\Console\Commands;

use App\Models\LeadAllocation;
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

        LeadAllocation::query()->where('reset_cap', 1)->update([
            'allocation_count' => 0,
            'auto_assignment_count' => 0,
            'manual_assignment_count' => 0,
            'max_capacity' => 20,
        ]);

        info('Scheduler has reset counts for all users where reset_cap was true');

        return 0;
    }

}
