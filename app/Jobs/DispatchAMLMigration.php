<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

class DispatchAMLMigration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    public $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('renewals');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Artisan::call('update-aml-status:cron');
    }
}
