<?php

namespace App\Jobs;

use App\Services\CarQuoteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * run this job to after upload & create lead to fetch plans of newly created quote
 */
class GetCarQuotePlansJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $uuid;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(CarQuoteService $carQuoteService)
    {
       return $carQuoteService->getPlans($this->uuid);
    }
}
