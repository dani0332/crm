<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateHealthStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'UpdateHealthStatus:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the Health leads with follow up status to Lost when last modified date is greater than 30 days';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        info('UpdateHealthStatus Command Commented as per new FR');

        return 0;
    }
}
