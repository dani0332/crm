<?php

namespace App\Console;

use App\Console\Commands\PolicyIssuanceCommand;
use App\Console\Commands\PolicyIssuanceDataCleanUpCommand;
use App\Console\Commands\SageProcessesMarkFailedCommand;
use App\Console\Commands\UpdateManualOffline;
use App\Jobs\CarLost\CarSoldResubmissions;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Stringable;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\AddBatchNumber::class,
        Commands\AddBatchNumberNonMotors::class,
        Commands\Dtt::class,
        Commands\DttFollowUp::class,
        Commands\TierAssignment::class,
        Commands\UpdateUserStatus::class,
        Commands\QuoteAllocation::class,
        Commands\LeadsReassignment::class,
        Commands\ResetLeadAllocationCounts::class,
        Commands\QuoteSyncUpdateCommand::class,
        Commands\UpdateStaleLeads::class,
        Commands\AutomateActivitiesCommand::class,
        Commands\PaymentOverdueStatus::class,
        Commands\AlfredFollowUpSchedulerCommand::class,
        Commands\ProcessCCPaymentsCommand::class,
        Commands\SageProcessesCommand::class,
        Commands\SageProcessDataCleanUpCommand::class,
        Commands\TravelRenewalLeads::class,
        SageProcessesMarkFailedCommand::class,
        PolicyIssuanceCommand::class,
        PolicyIssuanceDataCleanUpCommand::class,
        Commands\CorplineDataMigration::class,
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

        $schedule->command('PaymentOverdueStatus:cron')->everyMinute()->onOneServer()->withoutOverlapping(5);
        $schedule->command('ProcessCCPaymentsCommand:cron')->everyMinute()->onOneServer()->withoutOverlapping(1);

        $schedule->command('SendPaymentEmail:cron')->timezone('Asia/Dubai')->dailyAt('10:00')->onOneServer()->withoutOverlapping();

        $schedule->command('PaymentExpireNotification:cron')
            ->timezone('Asia/Dubai')
            ->hourly()
            ->between('9:00', '18:00')
            ->onOneServer()
            ->withoutOverlapping();

        /*$schedule->job(new UnconSubmissionReminder)
        ->tuesdays()
        ->fridays()
        ->withoutOverlapping(1)->onOneServer()
        ->at('9:00');*/

        $schedule->command('InstantAlfredNotification:cron')->everyMinute()->onOneServer()->withoutOverlapping();

        // send leads which are resubmitted for car sold approval yesterday
        $schedule->job((new CarSoldResubmissions))
            ->daily()
            ->withoutOverlapping()->onOneServer()
            ->at('9:00');

        $schedule
            ->command('AddBatchNumber:cron')->timezone('Asia/Dubai')->weeklyOn(1, '0:00')->onOneServer()->withoutOverlapping(5);

        $schedule
            ->command('AddBatchNumberNonMotors:cron')->timezone('Asia/Dubai')->weeklyOn(1, '0:00')->onOneServer()->withoutOverlapping(5);

        $schedule->command('QuoteAllocation:cron')->everyFiveMinutes()->onOneServer()->withoutOverlapping(8);

        $schedule->command('LeadsReassignment:cron')->everyFiveMinutes()->onOneServer()->withoutOverlapping(8);

        $schedule->command('ResetLeadAllocationCounts:cron')->timezone('Asia/Dubai')->dailyAt('00:00')->onOneServer()->withoutOverlapping();

        $schedule->command('QuoteSyncUpdate:cron')
            ->everyThreeMinutes()
            ->onOneServer()
            ->withoutOverlapping(5)
            ->onSuccess(function (Stringable $output) {
                info('----------- QuoteSyncJob Completed -----------'.$output);
            })
            ->onFailure(function (Stringable $output) {
                info('----------- QuoteSyncJob Failed -----------'.$output);
            });

        $schedule->command('QuoteSyncCleanup:cron')->dailyAt('03:00')->onOneServer()->withoutOverlapping(30);
        $schedule->command(UpdateManualOffline::class)
            ->timezone('Asia/Dubai')
            ->dailyAt('08:58')
            ->unlessBetween(
                Carbon::now()->next(Carbon::SATURDAY)->startOfDay(),
                Carbon::now()->next(Carbon::SUNDAY)->endOfDay()
            )
            ->onOneServer()
            ->withoutOverlapping(1);

        $schedule->command('UpdateStaleLeads:cron')->timezone('Asia/Dubai')->dailyAt('00:01')->onOneServer()->withoutOverlapping();

        $schedule->command('ActivitiesAutomate:cron')->timezone('Asia/Dubai')->dailyAt('00:01')->onOneServer()->withoutOverlapping();

        $schedule->command('Dtt')->timezone('Asia/Dubai')->dailyAt('09:00')->onOneServer()->withoutOverlapping();
        $schedule->command('Dtt:followup')->timezone('Asia/Dubai')->dailyAt('11:45')->onOneServer()->withoutOverlapping();

        $schedule->command('DttHealth')->timezone('Asia/Dubai')->dailyAt('09:03')->onOneServer()->withoutOverlapping();
        $schedule->command('DttHealthFollowUp')->timezone('Asia/Dubai')->dailyAt('11:48')->onOneServer()->withoutOverlapping();

        $schedule->command('sage-processes:run')->timezone('Asia/Dubai')->everyMinute()->onOneServer()->withoutOverlapping(4);
        $schedule->command('sage-process:cleanup')->timezone('Asia/Dubai')->dailyAt('00:30')->onOneServer()->withoutOverlapping();
        $schedule->command('sage-processes:mark-failed')->timezone('Asia/Dubai')->everyFiveMinutes()->onOneServer()->withoutOverlapping(8);
        $schedule->command('leads:process-travel-renewals')->timezone('Asia/Dubai')->dailyAt('00:00')->onOneServer()->withoutOverlapping();

        $schedule->command('policy-issuance-automation:run')->timezone('Asia/Dubai')->everyMinute()->onOneServer()->withoutOverlapping(4);
        $schedule->command('policy-issuance-automation:cleanup')->timezone('Asia/Dubai')->dailyAt('01:00')->onOneServer()->withoutOverlapping();

        // $schedule->command('alfred:followupEmails')->timezone('Asia/Dubai')->weekly()->mondays()->at('11:00')->onOneServer()->withoutOverlapping();

        // $schedule->command('CorplineDataMigration:cron')->timezone('Asia/Dubai')->dailyAt('10:50')
        //     ->onOneServer()
        //     ->withoutOverlapping()
        //     ->onSuccess(function (Stringable $output) {
        //         info('----------- Business Data Migrations Completed -----------'.$output);
        //     })
        //     ->onFailure(function (Stringable $output) {
        //         info('----------- Business Data Migrations Failed -----------'.$output);
        //     });

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
