<?php

namespace App\Console\Commands;

use App\Jobs\TierAssignmentJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TierAssignment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'TierAssignment:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tier Assignment cron';

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
        Log::info('Tier Assignment Cron Triggered');
        dispatch(new TierAssignmentJob());
    }
}
