<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateStaleLeads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'UpdateStaleLeads:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the leads with flag Stale when last modified date is greater than 30 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // This is not applied once the lead status reach 'Transaction approved, Policy Documents Pending, Policy issued, Policy sent to customer and Policy booked.
//        Additionally, this condition should not be applied when the status is "Lost", "Fake", "Duplicate", "Cancellation Pending", "Policy Cancelled
//        Health
//Corpline
//Home
//Pet
//Cycle
//Yacht
    }

}
