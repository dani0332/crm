<?php

namespace App\Jobs;

use Exception;
use App\Services\CRUDService;
use App\Services\UserService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use App\Services\HealthEmailService;
use App\Services\HealthQuoteService;
use App\Services\HttpRequestService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class SendHealthOCBIntroEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $quoteUuid;
    protected $healthQuoteService;
    protected $sendEmailCustomerService;
    private $previousAdvisor;
    private $triggerSICWorkflow;
    public $tries = 3;
    public $timeout = 60;
    public $backoff = 10;
    /**
     * Create a new job instance.
     */
    public function __construct($quoteUuid,$previousAdvisor, $triggerSICWorkflow = false)
    {
        $this->quoteUuid = $quoteUuid;
        $this->previousAdvisor = $previousAdvisor;
        $this->triggerSICWorkflow = $triggerSICWorkflow;
    }
    /**
     * Execute the job.
     */

     public function handle( HealthEmailService $healthEmailService, HealthQuoteService $healthQuoteService): void
     {
         try {
             $lead =$this->healthQuoteService->getEntity($this->quoteUuid);
             if (! $lead) {
                 info('SendHealthOCBIntroEmailJob - Lead not found for uuid: '.$this->quoteUuid);
                 return;
             }
             if ($lead->sic_flow_enabled) {
                 info('SendHealthOCBIntroEmailJob - SIC work flow is enabled on this lead already : '.$this->quoteUuid);
                 return;
             } else {
                     info('SendHealthOCBIntroEmailJob - SIC work flow is not enabled on this lead : '.$this->quoteUuid);
                     // Retrieve plans with available ratings for the given lead
                     $plans = $this->healthQuoteService->getQuotePlans($lead->uuid);

                     $responseCode = $healthEmailService->sendHealthOCBIntroEmail($plans, $lead,$this->previousAdvisor,$this->healthQuoteService, $this->triggerSICWorkflow);
                     if (in_array($responseCode, [200, 201])) {
                         info('SendOCBIntroEmailJob - OCB INTRO Email Sent: '.$responseCode.' Customer Email Address: '.$lead->email.' Quote UuId: '.$this->quoteUuid);
                     } else {
                         Log::error('SendOCBIntroEmailJob - OCB INTRO Email Not Sent: '.$responseCode.' Customer EmailAddress:'.$lead->email);
                     }
                }

         }catch (Exception $e) {
             info('SendHealthOCBIntroEmailJob - Error: '.$e->getMessage().' with stack trace: '.$e->getTraceAsString());
         }
     }

}
