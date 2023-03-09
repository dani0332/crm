<?php

namespace App\Console;

use App\Console\Commands\UpdateHealthStatus;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\LeadAllocation::class,
        Commands\AddBatchNumber::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule
            ->command('LeadAllocation:cron')
            ->everyMinute()
            ->onOneServer()
            ->withoutOverlapping(1);

        $schedule
            ->command('AddBatchNumber:cron')
            ->timezone('Asia/Dubai')
            ->weeklyOn(1, '0:00')
            ->onOneServer()
            ->withoutOverlapping(1);

        $schedule->command(UpdateHealthStatus::class)
        ->timezone('Asia/Dubai')
        ->dailyAt('01:00')->onOneServer()
        ->withoutOverlapping(1);
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
