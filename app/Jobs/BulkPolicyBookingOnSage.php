<?php

namespace App\Jobs;

use App\Services\SageApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BulkPolicyBookingOnSage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $request;

    /**
     * Create a new job instance.
     */
    public function __construct($request)
    {
        $this->request = $request;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $quoteType = $this->request->model_type;
        $quoteIDs = $this->request->selectedQuoteIds;
        $quoteErrors = collect([]);
        foreach ($quoteIDs as $quoteID) {
            $quote = $this->getQuoteObject($quoteType, $quoteID);
            if ($quote) {
                $response = (new SageApiService)->postBookPolicyToSage($this->request, $quote);
                if (! $response['status']) {
                    /* Log error in globel table for this lead */
                }

            }
        }
    }
}
