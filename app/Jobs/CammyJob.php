<?php

namespace App\Jobs;

use App\Services\CammyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CammyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 20;
    public $backoff = 360;
    private $lead = null;
    private $trigger = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($lead, $trigger)
    {
        $this->lead = $lead;
        $this->trigger = $trigger;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(CammyService $cammyService)
    {
        return $cammyService->sync($this->lead, $this->trigger);
    }
}
