<?php

namespace App\Console\Commands;

use App\Jobs\AddBatchNumberJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AddBatchNumber extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'AddBatchNumber:cron';

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
        Log::info('Add Batch Command Started');
        dispatch(new AddBatchNumberJob());
    }
}
