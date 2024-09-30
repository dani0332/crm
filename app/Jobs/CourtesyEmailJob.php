<?php

namespace App\Jobs;

use App\Facades\Capi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CourtesyEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = 360;
    private $quoteData;

    /**
     * Create a new job instance.
     */
    public function __construct($quoteData)
    {
        $this->quoteData = $quoteData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $response = Capi::request('/api/v1-trigger-courtesy-email-sib-workflow', 'post', $this->quoteData);

        info('Courtesy Email CAPI - Payload  : '.json_encode($this->quoteData).' - Response - : '.json_encode($response));
    }
}
