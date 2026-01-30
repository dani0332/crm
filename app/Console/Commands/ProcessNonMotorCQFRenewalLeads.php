<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ApplicationStorageEnums;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Services\CQF\NonMotor\NonMotorCQFRenewalService;
use App\Services\Logger\LoggerService;
use Illuminate\Console\Command;

class ProcessNonMotorCQFRenewalLeads extends Command
{
    protected $signature = 'leads:process-non-motor-cqf-renewals';
    protected $description = 'Retrieves and processes non-motor (Bike, Home, Pet, etc.) CQF renewal leads for upcoming policy renewals. Excludes Health and Business.';

    public function handle(): int
    {
        LoggerService::startQuoteLogging(self::class, LoggerFeatureEnum::NON_MOTOR_CQF_RENEWALS);

        $isNonMotorCQFRenewals = getAppStorageValueByKey(ApplicationStorageEnums::NON_MOTOR_CQF_RENEWALS_SWITCH);

        if ($isNonMotorCQFRenewals) {
            LoggerService::info('Starting process to retrieve non-motor CQF renewal leads');
            app(NonMotorCQFRenewalService::class)->processNonMotorCQFRenewalLeads();
            LoggerService::info('Completed process to retrieve non-motor CQF renewal leads');
        } else {
            LoggerService::info('Non-motor CQF renewals switch is disabled');
        }

        return self::SUCCESS;
    }
}
