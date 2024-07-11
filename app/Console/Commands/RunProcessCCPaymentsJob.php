<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\JobsProcessCCPaymentsJob;

class RunProcessCCPaymentsJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:run-process-c-c-payments-job';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to process CC payments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ProcessCCPaymentsJob::dispatch();
        $this->info('ProcessCCPaymentsJob has been dispatched.');
        return 0;

    }
}
