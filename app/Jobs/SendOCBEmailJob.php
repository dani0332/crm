<?php

namespace App\Jobs;

use App\Services\SendOCBEmailService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOCBEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $quoteUuid;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($quoteUuid)
    {
        $this->quoteUuid = $quoteUuid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(SendOCBEmailService $sendOCBEmailService)
    {
        $this->sendOCBEmailService = $sendOCBEmailService;

        try {
            $this->sendOCBEmailService->processOCBEmail($this->quoteUuid);
        } catch (Exception $e) {
            Log::info('OCB Email Process Error: '.$e->getMessage());
        }
    }
}
