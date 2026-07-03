<?php

namespace App\Console\Commands;

use App\Jobs\Health\ActivateScheduledHealthPlansJob;
use App\Services\Logger\LoggerService;
use Illuminate\Console\Command;

class ActivateScheduledHealthPlansCommand extends Command
{
    protected $signature = 'health-plans:activate-scheduled';
    protected $description = 'Activate scheduled health plans, rate controls, and rates whose effective_from date has been reached';

    public function handle(): void
    {
        LoggerService::info('cmd:'.self::class.' fn:'.__FUNCTION__.' Started');
        dispatch(new ActivateScheduledHealthPlansJob);
        LoggerService::info('cmd:'.self::class.' fn:'.__FUNCTION__.' Ended');
    }
}
