<?php

namespace App\Console;

use App\Console\Commands\ActivateScheduledHealthPlansCommand;
use App\Console\Commands\ActivityLogCleanupCommand;
use App\Console\Commands\PolicyBulkSendDocuments;
use App\Console\Commands\PolicyIssuanceCommand;
use App\Console\Commands\PolicyIssuanceDataCleanUpCommand;
use App\Console\Commands\PolicyIssuanceMarkFailedCommand;
use App\Console\Commands\SageProcessesMarkFailedCommand;
use App\Console\Commands\UpdateManualOffline;
use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTypes;
use App\Jobs\CarLost\CarSoldResubmissions;
use App\Jobs\PqaAllocationBackupJob;
use App\Jobs\ResetPqaAllocationCountJob;
use App\Jobs\SLAMonitoringJob;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Event;
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
        Commands\UpdateUserStatus::class,
        Commands\RetryCarAllocation::class,
        Commands\RetryCarRevivalAllocation::class,
        Commands\RetryHealthAllocation::class,
        Commands\RetryTravelAllocation::class,
        Commands\RetryBikeAllocation::class,
        Commands\RetryAllocation::class,
        Commands\LeadsReassignment::class,
        Commands\ResetLeadAllocationCounts::class,
        Commands\QuoteSyncUpdateCommand::class,
        Commands\UpdateStaleLeads::class,
        Commands\AutomateActivitiesCommand::class,
        Commands\PaymentOverdueStatus::class,
        Commands\ProcessCCPaymentsCommand::class,
        Commands\SageProcessesCommand::class,
        Commands\SageProcessDataCleanUpCommand::class,
        Commands\TravelRenewalLeads::class,
        Commands\CaptureEPPaymentsCommand::class,
        Commands\MisReportCommand::class,
        SageProcessesMarkFailedCommand::class,
        PolicyIssuanceCommand::class,
        PolicyIssuanceDataCleanUpCommand::class,
        PolicyIssuanceMarkFailedCommand::class,
        PolicyBulkSendDocuments::class,
        ActivityLogCleanupCommand::class,
        ActivateScheduledHealthPlansCommand::class,
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
        $schedule->command('send-payment-email-to-advisor:cron')->timezone('Asia/Dubai')->dailyAt('12:00')->onOneServer()->withoutOverlapping();

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

        $schedule->command('RetryCarAllocation:cron')->everyFiveMinutes()->onOneServer()->withoutOverlapping(8);
        $schedule->command('RetryCarRevivalAllocation:cron')->everyFiveMinutes()->onOneServer()->withoutOverlapping(8);
        $schedule->command('RetryHealthAllocation:cron')->everyFiveMinutes()->onOneServer()->withoutOverlapping(8);
        $schedule->command('RetryTravelAllocation:cron')->everyFiveMinutes()->onOneServer()->withoutOverlapping(8);
        $schedule->command('RetryBikeAllocation:cron')->everyFiveMinutes()->onOneServer()->withoutOverlapping(8);
        $schedule->command('RetryAllocation:cron --quoteType="Group Medical"')->name('retry_allocation:cron:group_medical')->everyFiveMinutes()->onOneServer()->withoutOverlapping(8);
        $schedule->command('RetryAllocation:cron --quoteType=Home')->name('retry_allocation:cron:home')->everyFiveMinutes()->onOneServer()->withoutOverlapping(8);
        $schedule->command('RetryAllocation:cron --quoteType=Life')->name('retry_allocation:cron:life')->everyFiveMinutes()->onOneServer()->withoutOverlapping(8);
        $schedule->command('RetryAllocation:cron --quoteType=CorpLine')->name('retry_allocation:cron:corpline')->everyFiveMinutes()->onOneServer()->withoutOverlapping(8);
        $schedule->command('RetryAllocation:cron --quoteType=Cycle')->name('retry_allocation:cron:cycle')->everyFiveMinutes()->onOneServer()->withoutOverlapping(8);
        $schedule->command('RetryAllocation:cron --quoteType=Pet')->name('retry_allocation:cron:pet')->everyFiveMinutes()->onOneServer()->withoutOverlapping(8);
        $schedule->command('RetryAllocation:cron --quoteType=Yacht')->name('retry_allocation:cron:yacht')->everyFiveMinutes()->onOneServer()->withoutOverlapping(8);
        $schedule->command('RetryAllocation:cron --quoteType=Savings')->name('retry_allocation:cron:savings')->everyFiveMinutes()->onOneServer()->withoutOverlapping(8);
        $schedule->command('RetryAllocation:cron --quoteType=Cyber')->name('retry_allocation:cron:cyber')->everyFiveMinutes()->onOneServer()->withoutOverlapping(8);
        $schedule->command('RetryAllocation:cron --quoteType=Device')->name('retry_allocation:cron:device')->everyFiveMinutes()->onOneServer()->withoutOverlapping(8);

        $schedule->command('LeadsReassignment:cron')->everyFiveMinutes()->onOneServer()->withoutOverlapping(8);

        $schedule->command('ResetLeadAllocationCounts:cron')->timezone('Asia/Dubai')->dailyAt('00:00')->onOneServer()->withoutOverlapping();

        $schedule->command('activitylog:cleanup')->timezone('Asia/Dubai')->dailyAt('00:00')->onOneServer()->withoutOverlapping();
        $schedule->command('send-failed-ila-leads --quoteType=Car')->name('send-failed-ila-leads:cron:car')->timezone('Asia/Dubai')->everyFifteenMinutes()->between('10:00', '23:00')->onOneServer()->withoutOverlapping();
        $schedule->command('send-failed-ila-leads --quoteType=Bike')->name('send-failed-ila-leads:cron:bike')->timezone('Asia/Dubai')->everyFifteenMinutes()->between('10:00', '23:00')->onOneServer()->withoutOverlapping();
        $schedule->command('send-failed-ila-leads --quoteType=Health')->name('send-failed-ila-leads:cron:health')->timezone('Asia/Dubai')->everyFifteenMinutes()->between('10:00', '23:00')->onOneServer()->withoutOverlapping();
        $schedule->command('send-failed-ila-leads --quoteType=Life')->name('send-failed-ila-leads:cron:life')->timezone('Asia/Dubai')->everyFifteenMinutes()->between('10:00', '23:00')->onOneServer()->withoutOverlapping();
        $schedule->command('send-failed-ila-leads --quoteType=Travel')->name('send-failed-ila-leads:cron:travel')->timezone('Asia/Dubai')->everyFifteenMinutes()->between('10:00', '23:00')->onOneServer()->withoutOverlapping();
        $schedule->command('send-failed-ila-leads --quoteType=Home')->timezone('Asia/Dubai')->name('send-failed-ila-leads:cron:home')->everyFifteenMinutes()->between('10:00', '23:00')->onOneServer()->withoutOverlapping();
        $schedule->command('send-failed-ila-leads --quoteType=Pet')->name('send-failed-ila-leads:cron:pet')->timezone('Asia/Dubai')->everyFifteenMinutes()->between('10:00', '23:00')->onOneServer()->withoutOverlapping();
        $schedule->command('send-failed-ila-leads --quoteType=Cycle')->name('send-failed-ila-leads:cron:cycle')->timezone('Asia/Dubai')->everyFifteenMinutes()->between('10:00', '23:00')->onOneServer()->withoutOverlapping();
        $schedule->command('send-failed-ila-leads --quoteType=Savings')->name('send-failed-ila-leads:cron:savings')->timezone('Asia/Dubai')->everyFifteenMinutes()->between('10:00', '23:00')->onOneServer()->withoutOverlapping();
        $schedule->command('send-failed-ila-leads --quoteType="Group Medical"')->name('send-failed-ila-leads:cron:group_medical')->timezone('Asia/Dubai')->everyFifteenMinutes()->between('10:00', '23:00')->onOneServer()->withoutOverlapping();
        $schedule->command('send-failed-ila-leads --quoteType=CorpLine')->name('send-failed-ila-leads:cron:corpline')->timezone('Asia/Dubai')->everyFifteenMinutes()->between('10:00', '23:00')->onOneServer()->withoutOverlapping();
        $schedule->command('send-failed-ila-leads --quoteType=Yacht')->name('send-failed-ila-leads:cron:yacht')->timezone('Asia/Dubai')->everyFifteenMinutes()->between('10:00', '23:00')->onOneServer()->withoutOverlapping();
        $schedule->command('send-failed-ila-leads --quoteType=Jetski')->name('send-failed-ila-leads:cron:jetski')->timezone('Asia/Dubai')->everyFifteenMinutes()->between('10:00', '23:00')->onOneServer()->withoutOverlapping();

        $schedule->job(new PqaAllocationBackupJob(QuoteTypes::CORPLINE))->name('pqa-backup:corpline')->timezone('Asia/Dubai')->everyFifteenMinutes()->onOneServer()->withoutOverlapping(8);
        $schedule->job(new PqaAllocationBackupJob(QuoteTypes::HEALTH))->name('pqa-backup:health')->timezone('Asia/Dubai')->everyFifteenMinutes()->onOneServer()->withoutOverlapping(8);
        $schedule->job(new ResetPqaAllocationCountJob)->timezone('Asia/Dubai')->dailyAt('00:00')->onOneServer()->withoutOverlapping();

        $schedule->command('QuoteSyncUpdate:cron')
            ->everyThreeMinutes()
            ->onOneServer()
            ->withoutOverlapping(5)
            ->onSuccess(function (Stringable $output) {
                LoggerService::info('----------- QuoteSyncJob Completed -----------'.$output);
            })
            ->onFailure(function (Stringable $output) {
                LoggerService::info('----------- QuoteSyncJob Failed -----------'.$output);
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

        $schedule->command('DttLife')->timezone('Asia/Dubai')->dailyAt('09:10')->onOneServer()->withoutOverlapping();

        $schedule->command('DttHome')->timezone('Asia/Dubai')->dailyAt('09:06')->onOneServer()->withoutOverlapping();

        $schedule->command('DttHealth')->timezone('Asia/Dubai')->dailyAt('09:03')->onOneServer()->withoutOverlapping();
        $schedule->command('DttHealthFollowUp')->timezone('Asia/Dubai')->dailyAt('11:48')->onOneServer()->withoutOverlapping();

        $schedule->command('sage-processes:run')->timezone('Asia/Dubai')->everyMinute()->onOneServer()->withoutOverlapping(4);
        // $schedule->command('sage-process:cleanup')->timezone('Asia/Dubai')->dailyAt('00:30')->onOneServer()->withoutOverlapping();
        $schedule->command('sage-processes:mark-failed')->timezone('Asia/Dubai')->everyFiveMinutes()->onOneServer()->withoutOverlapping(8);
        $schedule->command('leads:process-travel-renewals')->timezone('Asia/Dubai')->dailyAt('00:50')->onOneServer()->withoutOverlapping();
        $schedule->command('leads:process-car-cqf-renewals')->timezone('Asia/Dubai')->dailyAt('03:00')->onOneServer()->withoutOverlapping();
        $this->scheduleWithEnvironment(
            $schedule,
            'policy-issuance-automation:run',
            default: fn ($event) => $event->timezone('Asia/Dubai')->everyThreeMinutes()->onOneServer()->withoutOverlapping(4),
            environments: [
                'test' => fn ($event) => $event->timezone('Asia/Dubai')->everyMinute()->onOneServer()->withoutOverlapping(4),
                'uat' => function ($event) {
                    $environment = app()->environment();
                    LoggerService::info("policy-issuance-automation:run skipped on {$environment}");

                    return $event->skip(fn () => true);
                },
            ]
        );
        $schedule->command('aml-screening-automation:run')->timezone('Asia/Dubai')->everyFiveMinutes()->onOneServer()->withoutOverlapping();
        $schedule->command('aml-screening-automation:cleanup')->timezone('Asia/Dubai')->dailyAt('00:30')->onOneServer()->withoutOverlapping();
        $schedule->command('policy-issuance-automation:cleanup')->timezone('Asia/Dubai')->dailyAt('01:00')->onOneServer()->withoutOverlapping();
        $schedule->command('policy-issuance:mark-failed')->timezone('Asia/Dubai')->everyFifteenMinutes()->onOneServer()->withoutOverlapping(8);

        $schedule->command('quotes-syncing:retry')->timezone('Asia/Dubai')->everyFiveMinutes()->onOneServer()->withoutOverlapping();

        $schedule->command('horizon:snapshot')->everyFiveMinutes()->onOneServer()->withoutOverlapping();
        $schedule->command('remove-pcp-tag')->timezone('Asia/Dubai')->dailyAt('00:01')->onOneServer()->withoutOverlapping();

        $schedule->command('health-plans:activate-scheduled')->timezone('Asia/Dubai')->dailyAt('00:00')->onOneServer()->withoutOverlapping();

        $schedule->command('ep:capture-payments')
            ->everyThirtyMinutes()
            ->onOneServer()
            ->withoutOverlapping(30)
            ->onSuccess(function (Stringable $output) {
                LoggerService::info('----------- CaptureEPPaymentsJob Completed -----------', extra: [
                    'output' => $output,
                ]);
            })
            ->onFailure(function (Stringable $output) {
                LoggerService::info('----------- CaptureEPPaymentsJob Failed -----------', extra: [
                    'output' => $output,
                ]);
            });
        $schedule->job(new SLAMonitoringJob)->everyFiveMinutes()->onOneServer()->withoutOverlapping(1);

        // Schedule MIS Report command with environment-specific configurations
        $this->scheduleWithEnvironment(
            $schedule,
            'mis-report:run',
            default: fn ($event) => $event->timezone('Asia/Dubai')->mondays()->at('08:00')->onOneServer()->withoutOverlapping(),
            environments: ['staging' => fn ($event) => $event->hourly()->onOneServer()->withoutOverlapping()]
        );

        $schedule
            ->command(
                'reports:conversion-optimization-scheduled-export '.ApplicationStorageEnums::CONVERSION_OPTIMIZATION_SCHEDULED_EXPORT_PARAMS
            )
            ->mondays()
            ->at('10:00')
            ->timezone('Asia/Dubai')
            ->onOneServer()
            ->withoutOverlapping();

        // Alternate recipient set: add a new ApplicationStorageEnums constant + application_storage row, then e.g.:
        // $schedule->command('reports:conversion-optimization-scheduled-export OTHER_KEY_NAME')->environments(['production'])->mondays()->at('10:00')->onOneServer()->withoutOverlapping();
    }

    /**
     * Schedule a command with environment-specific configurations.
     *
     * @param  string|class-string  $command  Command string (e.g., 'command:name') or command class name
     * @param  \Closure  $default  Default schedule configuration callback
     * @param  array<string, \Closure>  $environments  Environment-specific schedule configurations
     * @return Event
     */
    protected function scheduleWithEnvironment(
        Schedule $schedule,
        string $command,
        \Closure $default,
        array $environments = []
    ) {
        $event = $schedule->command($command);

        $currentEnvironment = app()->environment();

        // Check if there's an environment-specific configuration
        if (isset($environments[$currentEnvironment])) {
            return $environments[$currentEnvironment]($event);
        }

        // Apply default configuration
        return $default($event);
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
