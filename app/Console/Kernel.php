<?php

namespace App\Console;

use App\Console\Commands\UpdateHealthStatus;
use App\Jobs\LeadAllocationJob;
use App\Jobs\TierAssignmentJob;
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
        Commands\Dtt::class,
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

         $schedule->command('Dtt')->everyFiveMinutes()->onOneServer()->withoutOverlapping(1);

        //$schedule->job(new LeadAllocationJob)->everyMinute()->withoutOverlapping(1)->onOneServer();

        $schedule->job(new TierAssignmentJob)->everyTwoMinutes()->withoutOverlapping(1)->onOneServer();

        $schedule
            ->command('AddBatchNumber:cron')
            ->timezone('Asia/Dubai')
            ->weeklyOn(1, '0:00')
            ->onOneServer()
            ->withoutOverlapping(1);

        //Disabling - Enable for RM Deployment
        // $schedule->command(UpdateHealthStatus::class)
        // ->timezone('Asia/Dubai')
        // ->dailyAt('01:00')->onOneServer()
        // ->withoutOverlapping(1);

        $schedule->command('telescope:prune --hours=48')->daily()
            ->onOneServer()
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
