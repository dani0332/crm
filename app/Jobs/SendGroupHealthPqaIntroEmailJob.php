<?php

namespace App\Jobs;

use App\Models\BusinessQuote;
use App\Services\SendEmailCustomerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendGroupHealthPqaIntroEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = 360;

    /**
     * Create a new job instance.
     */
    public function __construct(public BusinessQuote $businessQuote) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        app(SendEmailCustomerService::class)->sendGroupHealthPqaIntroEmail($this->businessQuote);
    }
}
