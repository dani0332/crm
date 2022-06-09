<?php

namespace App\Console\Commands;

use App\Jobs\LeadAllocationJob;
use App\Services\LeadAllocationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class LeadAllocation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'LeadAllocation:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        Log::channel('daily')->info('Lead Allocation Command Started');

        dispatch(new LeadAllocationJob());
    }
}
