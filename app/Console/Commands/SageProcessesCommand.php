<?php

namespace App\Console\Commands;

use App\Enums\SageEnum;
use App\Jobs\BookPolicyOnSageJob;
use App\Jobs\SendUpdateSageJob;
use App\Models\SageProcess;
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
        info('cmd:SageProcessesCommand - Policy Booking Command Started');

        (new SageApiService)->scheduleSageProcesses();

        info('cmd:SageProcessesCommand - Sage Policy Booking Command Ended');
    }

}
