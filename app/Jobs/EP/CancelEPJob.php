<?php

namespace App\Jobs\EP;

use App\Repositories\EmbeddedProductRepository;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CancelEPJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = 180;
    private $requestData = null;

    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        $this->requestData = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {

            $uuid = $this->requestData['uuid'];
            info("Cancel EP Payment requeset: - {$uuid} - ".json_encode($this->requestData).' ---- ');
            $response = EmbeddedProductRepository::cancelPayment($this->requestData);
            info("Auto cancel EP Payment response: - {$uuid} - ".json_encode($response).' ---- ');

        } catch (Exception $e) {
            Log::error('Auto cancel EP Payment - ERROR:'.$e->getMessage());
        }
    }
}
