<?php

namespace App\Jobs;

use App\Services\MyAlfredService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;

class AlfredFollowupEmailJob implements ShouldQueue, StackableJob
{
    use Dispatchable, InteractsWithQueue, Queueable, Stackable;

    /**
     * Create a new job instance.
     */
    private $customer;

    public $tries = 3;
    public $timeout = 30;
    public $backoff = 10;

    public function __construct($customer)
    {
        $this->customer = $customer;
    }

    /**
     * Execute the job.
     */
    public function handle(MyAlfredService $myAlfredService)
    {
        if (! empty($this->customer)) {
            $myAlfredService->sendingAlfredFollowupEmail($this->customer);
        }

        return true;
    }
}
