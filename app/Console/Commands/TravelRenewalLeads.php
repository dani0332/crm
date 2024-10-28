<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TravelRenewalService;

class TravelRenewalLeads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'travel-renewal-leads:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        echo "get renewals travel-renewal- leads";
        app(TravelRenewalService::class)->getTravelRenewalLeads();
        echo "done";
    }
}
