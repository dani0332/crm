<?php

namespace App\Console\Commands;

use App\Services\SageApiService;
use Cache;
use Illuminate\Console\Command;

class SageProcessesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sage-processes:run {insurer?}';

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
        $insurer = $this->argument('insurer');
        $sageProcessCommandLock = Cache::lock('sage-processes-run-lock', 20); // acquire lock for 30 seconds

        info('cmd:SageProcessesCommand - Sage Policy or Endorsements Booking Command Started ', ['insurer' => $insurer]);

        if ($sageProcessCommandLock->get()) {
            (new SageApiService)->scheduleSageProcesses($insurer);

            $sageProcessCommandLock->release();
        } else {
            info('cmd:SageProcessesCommand - Sage Policy or Endorsements Booking Command is already running, skipping execution.', ['insurer' => $insurer]);
        }

        info('cmd:SageProcessesCommand - Sage Policy or Endorsements Booking Command Ended', ['insurer' => $insurer]);
    }

}
