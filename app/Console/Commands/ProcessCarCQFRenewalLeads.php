<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Services\CQF\CarCQFRenewalService;
use Illuminate\Console\Command;

class ProcessCarCQFRenewalLeads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:process-car-cqf-renewals';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieves and processes car cqf renewal leads for upcoming policy renewals.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isCarCQFRenewals = getAppStorageValueByKey(ApplicationStorageEnums::CAR_CQF_RENEWALS_SWITCH);
        if ($isCarCQFRenewals) {
            info('Starting process to retrieve car cqf renewal leads | Time: '.now());
            app(CarCQFRenewalService::class)->processCarCQFRenewalLeads();
            info('Completed process to retrieve car cqf renewal leads | Time: '.now());
        } else {
            info('Car CQF Renewals Switch is disabled | Time: '.now());
        }

    }
}
