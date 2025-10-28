<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Logger\LoggerService;
use App\Enums\DocumentTypeCode;
use App\Services\WAServices\CarWAService;
use Exception;
use App\Models\CarQuote;

class CarMissingDocReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $quoteUuid;

    public $tries = 3;
    public $timeout = 15;
    public $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct($quoteUuid)
    {
        $this->quoteUuid = $quoteUuid;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $lead = CarQuote::where('uuid', $this->quoteUuid)->first();
            if (! $lead) {
                LoggerService::info('CarMissingDocReminderJob - lead not found', ['uuid' => $this->quoteUuid]);
                return;
            }
        LoggerService::startQuoteLogging($this->quoteUuid);
            // Check for missing or incomplete required documents: Emirates ID, Mulkiya, and Driving Licence
         $requiredDocuments = [DocumentTypeCode::EMIRATES_ID, DocumentTypeCode::REGISTRATION_CARD_MULKIYA, DocumentTypeCode::DRIVING_LICENSE];
         $leadDocuments = $lead->documents()
         ->whereIn('document_type_code', $requiredDocuments)
         ->get()
         ->keyBy('document_type_code');
  
         $missingOrIncomplete = collect($requiredDocuments)->filter(function ($docType) use ($leadDocuments) {
             // Document is missing or not marked as complete/verified
             $doc = $leadDocuments->get($docType);
             return !$doc || !$doc->is_complete;
         });
         // Only send reminder if at least one required document is missing or incomplete
         if ($missingOrIncomplete->isNotEmpty()) {
             app(CarWAService::class)->sendCarMissingDocReminder($lead);
             LoggerService::info(self::class.' - sending reminder for missing documents');
         }
         else {
             LoggerService::info(self::class.' - all required documents are present and complete');
         }
         
        } catch (Exception $e) {
            LoggerService::error(self::class.' - error sending reminder for missing documents', ['uuid' => $this->quoteUuid, 'error' => $e->getMessage()]);
        }
    }
}
