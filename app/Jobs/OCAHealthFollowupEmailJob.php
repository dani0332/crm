<?php

namespace App\Jobs;

use App\Models\HealthQuote;
use Illuminate\Bus\Queueable;
use App\Enums\QuoteStatusEnum;
use App\Services\HealthEmailService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class OCAHealthFollowupEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $quoteUuid;

    public $tries = 3;
    public $timeout = 15;
    public $backoff = 60;

    public function __construct($quoteUuid)
    {
        $this->quoteUuid = $quoteUuid;
    }

    /**
     * Execute the job.
     */
    public function handle(HealthEmailService $healthEmailService)
    {
        try {
        $healthLead = HealthQuote::where('uuid', $this->quoteUuid)->first();
        if(empty($healthLead)) {
            info('OCAHealthFollowupEmailJob - Health Lead Not Found - Ref ID: '.$this->quoteUuid.'| Time: '.now());
            return;
        }
        if (empty($healthLead->advisor_id)) {
                if($healthLead->quote_status_id == QuoteStatusEnum::Quoted) {
                    info("sending oca  health email follow ups for Ref-ID : ".$healthLead->uuid." lead status id : ".$healthLead->quote_status_id.' : Quoted | Time: '.now());
                    // Send the Health OCA email using the HealthEmailService
                    $healthEmailService->triggerOCAFollowups($healthLead);
                }
                else {
                    info('OCAHealthFollowupEmailJob - Health Lead Not Triggered OCAFollowups with Quoted Status - Ref ID: '.$healthLead->uuid.'|Time: '.now());
                }
                if($healthLead->quote_status_id == QuoteStatusEnum::ApplicationPending) {
                    info("sending oca  health email follow ups for Ref-ID : ".$healthLead->uuid." lead status id : ".$healthLead->quote_status_id.' : ApplicationPending | Time: '.now());
                    // Send the Health OCA email using the HealthEmailService
                    $healthEmailService->triggerPendingHealthFollowupEmails($healthLead);
                }
                else {
                    info('OCAHealthFollowupEmailJob - Health Lead Not Triggered OCAFollowups with Quoted Status - Ref ID: '.$healthLead->uuid.'| Time: '.now());
                  }


        } else {
            info('OCAHealthFollowupEmailJob - Health Lead Advisor Available - Ref ID: '.$healthLead->uuid.'- Time: '.now());
        }
    } catch (\Throwable $th) {
        info('OCAHealthFollowupEmailJob - Exception'.$th->getMessage().' - Ref ID: '.$healthLead->uuid.'- Time: '.now());
    }
    }
}
