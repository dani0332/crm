<?php

namespace App\Console;

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
            ->command('LeadAllocation:cron')->everyMinute()->onOneServer()->withoutOverlapping(1);

        $schedule->command('Dtt')->timezone('Asia/Dubai')->everyFifteenMinutes()->onOneServer()->withoutOverlapping(1);
        $schedule->command('Dtt:followup')->timezone('Asia/Dubai')->daily()->onOneServer()->withoutOverlapping(1);

        //$schedule->job(new LeadAllocationJob)->everyMinute()->withoutOverlapping(1)->onOneServer();
        $schedule->job(new UnconSubmissionReminder)
            ->tuesdays()
            ->fridays()
            ->withoutOverlapping(1)->onOneServer()
            ->at('9:00');

        //send leads which are resubmitted for car sold approval yesterday
        $schedule->job((new CarSoldResubmissions))
            ->daily()
            ->withoutOverlapping(1)->onOneServer()
            ->at('9:00');

        $schedule
            ->command('TierAssignment:cron')->everyTwoMinutes()->onOneServer()->withoutOverlapping(1);

        $schedule
            ->command('AddBatchNumber:cron')->timezone('Asia/Dubai')->weeklyOn(1, '0:00')->onOneServer()->withoutOverlapping(1);

        $schedule
            ->command('telescope:prune --hours=48')->daily()->onOneServer()->withoutOverlapping(1);
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
