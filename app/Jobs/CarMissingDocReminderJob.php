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
use App\Enums\QuoteFlowType;
use App\Enums\QuoteTypes;
use App\Services\BirdService;

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
            if(app(BirdService::class)->isFollowupExecuted($this->quoteUuid, QuoteTypes::CAR->id(), QuoteFlowType::CAR_MISSING_DOC_REMINDER->value)) {
                LoggerService::info(self::class." - Car Missing Docs Reminder already executed for quote {$this->quoteUuid}");
               return;
            }
            $lead = CarQuote::where('uuid', $this->quoteUuid)->first();
            if (! $lead) {
                LoggerService::info(self::class." - Quote not found for quote {$this->quoteUuid}");
                return;
            }
            LoggerService::startQuoteLogging(QuoteTypes::CAR->refId($this->quoteUuid));
            // Check for missing or incomplete required documents: Emirates ID, Mulkiya, and Driving Licence
         $requiredDocuments = [DocumentTypeCode::EMIRATES_ID, DocumentTypeCode::REGISTRATION_CARD_MULKIYA, DocumentTypeCode::DRIVING_LICENSE];
         $leadDocuments = $lead->documents()
         ->whereIn('document_type_code', $requiredDocuments)
         ->get()
         ->keyBy('document_type_code');
        // Check if all required documents are present and complete
        $missingOrIncomplete = collect($requiredDocuments)->map(function ($docType) use ($leadDocuments) {
            $doc = $leadDocuments->get($docType);
            return ['document_type_code' => $docType, 'is_complete' => $doc ? true : false];
        })->values();

        if ($missingOrIncomplete->every(function ($item) {
            return $item['is_complete'];})) {
            LoggerService::info(self::class.' - all required documents are present and complete');
            return;
        } else {
            app(CarWAService::class)->sendCarMissingDocReminder($lead); 
            LoggerService::info(self::class.' - sending reminder for missing documents');
        }
         
        } catch (Exception $e) {
            LoggerService::error(self::class.' - error sending reminder for missing documents', ['uuid' => $this->quoteUuid, 'error' => $e->getMessage()]);
        }
    }
}
