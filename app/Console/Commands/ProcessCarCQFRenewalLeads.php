<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Services\CQF\CarCQFRenewalService;
use App\Services\Logger\LoggerService;
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
        LoggerService::startQuoteLogging(self::class, LoggerFeatureEnum::CAR_CQF_RENEWALS);

        $isCarCQFRenewals = getAppStorageValueByKey(ApplicationStorageEnums::CAR_CQF_RENEWALS_SWITCH);
        if ($isCarCQFRenewals) {
            LoggerService::info('Starting process to retrieve car cqf renewal leads ');
            app(CarCQFRenewalService::class)->processCarCQFRenewalLeads();
            LoggerService::info('Completed process to retrieve car cqf renewal leads ');
        } else {
            LoggerService::info('Car CQF Renewals Switch is disabled ');
        }

    }
}
