<?php

namespace App\Console\Commands;

use App\Services\TravelRenewalService;
use Illuminate\Console\Command;

class TravelRenewalLeads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:retrieve-travel-renewals';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieves and processes travel renewal leads for upcoming policy renewals.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        info("Starting process to retrieve travel renewal leads | Time: " . now());
        app(TravelRenewalService::class)->getTravelRenewalLeads();
        info("Completed process to retrieve travel renewal leads | Time: " . now());
    }
}
