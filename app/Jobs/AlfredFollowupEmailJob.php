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

    private $myAlfredService;
    public function __construct($customer)
    {
        $this->customer = $customer;
    }

    /**
     * Execute the job.
     */
    public function handle(MyAlfredService $myAlfredService)
    {
        if(!empty($this->customer)){
            $this->myAlfredService->sendingAlfredFollowupEmail($this->customer);
        }
        return true;
    }
}
