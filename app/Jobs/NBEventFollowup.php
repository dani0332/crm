<?php

namespace App\Jobs;

use App\Models\CarQuote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\EmailServices\CarEmailService;

class NBEventFollowup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = 60;
    private $uuid;
    private $templateType;
    /**
     * Create a new job instance.
     */
    public function __construct($uuid, $templateType)
    {
        $this->uuid = $uuid;
        $this->templateType = $templateType;
        //
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
            $lead = CarQuote::where('uuid', $this->uuid)->first();
            app(CarEmailService::class)->sendFollowupsEventForNB($lead, $this->templateType);
            info('Sending NBEventFollowup followups email for lead: '.$lead->uuid.' | Time: '.now());
    }

}
