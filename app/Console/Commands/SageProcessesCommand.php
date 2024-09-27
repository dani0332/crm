<?php

namespace App\Console\Commands;

use App\Services\SageApiService;
use Illuminate\Console\Command;

class SageProcessesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sage-processes:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process Sage Policy and Endorsements Booking single request per Insurance Provider';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        info('cmd:SageProcessesCommand - Sage Policy or Endorsements Booking Command Started');

        (new SageApiService)->scheduleSageProcesses();

        info('cmd:SageProcessesCommand - Sage Policy or Endorsements Booking Command Ended');
    }

}
