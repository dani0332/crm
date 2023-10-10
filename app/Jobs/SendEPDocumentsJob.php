<?php

namespace App\Jobs;

use App\Facades\PostMark;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendEPDocumentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $emailData = null;
    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {

        $this->emailData = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $response = PostMark::sendEmail($this->emailData);
            info('Ep send documents email response  - : '.json_encode($response));
        } catch (Exception $e) {
            Log::error('Ep send documents email ERROR:'.$e->getMessage());
        }
    }
}
