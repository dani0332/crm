<?php

namespace App\Console;

use App\Console\Commands\DailyInslyDataCapture;
use App\Console\Commands\InslyOldDataCapture;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        DailyInslyDataCapture::class,
        InslyOldDataCapture::class,
        Commands\FTCAcKEmail::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->exec(str_replace('php.ini', 'php', php_ini_loaded_file()).' '.getcwd().'/artisan inslyDataCaputre:daily >> '.getcwd().'/storage/logs/cron.log 2>&1')
        // ->days([Schedule::SUNDAY,Schedule::MONDAY,Schedule::TUESDAY,Schedule::WEDNESDAY,Schedule::THURSDAY])
        // ->between('20:00', '07:00')
        // ->hourly()
        // ->runInBackground()
        // ->withoutOverlapping();
        // // ->emailOutputOnFailure('ahsan.ashfaq@afia.ae')
        //echo(phpinfo());
        $schedule
        ->command('InslyOldDataCapture:all')
        ->timezone('Asia/Dubai')
        ->between('09:00', '07:00')
        ->everyThirtyMinutes()
        ->runInBackground()
        ->onOneServer()
        ->withoutOverlapping();


        $schedule->command('log:FTCAckEmail')
                 ->everyMinute();
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
