<?php

namespace App\Console;

use App\Jobs\HealthLeadAllocationJob;
use App\Jobs\CarLeadAllocationJob;
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
    ];

    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

        // Schedule CarLeadAllocationJob to run every minute without overlapping on one server
        $schedule->job(new CarLeadAllocationJob)
        ->everyMinute()
        ->withoutOverlapping(1)
        ->onOneServer();

        // Schedule HealthLeadAllocationJob to run every minute without overlapping on one server
        $schedule->job(new HealthLeadAllocationJob)
        ->everyMinute()
        ->withoutOverlapping(1)
        ->onOneServer();

        // Schedule TierAssignmentJob to run every two minutes without overlapping on one server
        $schedule->job(new TierAssignmentJob)
        ->everyTwoMinutes()
        ->withoutOverlapping(1)
        ->onOneServer();

        // Schedule AddBatchNumber:cron command to run every Monday at midnight in Asia/Dubai timezone without overlapping on one server
        $schedule->command('AddBatchNumber:cron')
        ->timezone('Asia/Dubai')
        ->weeklyOn(1, '0:00')
        ->onOneServer()
        ->withoutOverlapping(1);

        // Schedule telescope:prune command to run daily without overlapping on one server
        $schedule->command('telescope:prune --hours=48')
        ->daily()
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
