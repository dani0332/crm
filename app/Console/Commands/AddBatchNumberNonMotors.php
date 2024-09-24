<?php

namespace App\Console\Commands;

use App\Jobs\AddBatchNumberNonMotorsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AddBatchNumberNonMotors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'AddBatchNumberNonMotors:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create batch numbers for non-motors';

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
        Log::info('Add Batch Number Command Started for non-motors ');
        dispatch(new AddBatchNumberNonMotorsJob);
    }
}
