<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use App\Services\TravelRenewalService;
use Illuminate\Console\Command;

class TravelRenewalLeads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:process-travel-renewals';

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
        $isTravelRenewals = ApplicationStorage::where('key_name', ApplicationStorageEnums::TRAVEL_RENEWALS_SWITCH)->first();
        if ($isTravelRenewals && $isTravelRenewals->value == 1) {
            info('Starting process to retrieve travel renewal leads | Time: '.now());
            app(TravelRenewalService::class)->processTravelRenewalLeads();
            info('Completed process to retrieve travel renewal leads | Time: '.now());
        } else {
            info('Travel Renewals Switch is disabled | Time: '.now());
        }

    }
}
