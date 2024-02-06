<?php

namespace App\Console;

use App\Console\Commands\UpdateHealthStatus;
use App\Jobs\CarLost\CarSoldResubmissions;
use App\Jobs\CarLost\UnconSubmissionReminder;
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
        Commands\AddBatchNumber::class,
        Commands\TierAssignment::class,
        Commands\UpdateUserStatus::class,
        Commands\QuoteAllocation::class,
        Commands\LeadsReassignment::class,
        Commands\ResetLeadAllocationCounts::class,
        Commands\UpdateHealthStatus::class,
        Commands\QuoteSyncUpdateCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule
            ->command('UpdateUserStatus:cron')->everyMinute()->onOneServer()->withoutOverlapping(1);

        /*$schedule->job(new UnconSubmissionReminder)
            ->tuesdays()
            ->fridays()
            ->withoutOverlapping(1)->onOneServer()
            ->at('9:00');*/

        //send leads which are resubmitted for car sold approval yesterday
        $schedule->job((new CarSoldResubmissions))
            ->daily()
            ->withoutOverlapping(1)->onOneServer()
            ->at('9:00');

        $schedule
            ->command('AddBatchNumber:cron')->timezone('Asia/Dubai')->weeklyOn(1, '0:00')->onOneServer()->withoutOverlapping(1);

        $schedule
            ->command(UpdateHealthStatus::class)->timezone('Asia/Dubai')->dailyAt('01:00')->onOneServer()->withoutOverlapping(1);

        $schedule->command('QuoteAllocation:cron')->everyFiveMinutes()->onOneServer()->withoutOverlapping(1);

        $schedule->command('LeadsReassignment:cron')->everyFiveMinutes()->onOneServer()->withoutOverlapping(1);

        $schedule->command('ResetLeadAllocationCounts:cron')->timezone('Asia/Dubai')->dailyAt('23:59')->onOneServer()->withoutOverlapping(1);

        $schedule->command('QuoteSyncUpdate:cron')->everyFiveMinutes()->onOneServer()->withoutOverlapping(1);

        // This command need to be remove after deployment, because it's not required anymore.
        $schedule->command('UpdateLostStatus:cron')->timezone('Asia/Dubai')->tuesdays()->withoutOverlapping(1)->onOneServer()->at('17:00');

        $schedule->command('UpdateStaleLeads:cron')->timezone('Asia/Dubai')->dailyAt('23:59')->onOneServer()->withoutOverlapping(1);

        $schedule->command('ActivitiesAutomate:cron')->timezone('Asia/Dubai')->dailyAt('23:59')->onOneServer()->withoutOverlapping(1);

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
