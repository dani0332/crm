<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTagEnums;
use App\Enums\QuoteTypeId;
use App\Models\CarQuote;
use App\Models\QuoteTag;
use App\Services\BirdService;
use App\Services\CentralService;
use App\Services\Logger\LoggerService;
use App\Services\QuoteDocumentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PartnerPolicyDocumentJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, Queueable;

    public int $timeout = 100;
    public int $tries = 3;
    public int $uniqueFor = 3600;

    /** @var array<int, array<string, mixed>> Raw document records with doc_url / watermarked_doc_url */
    private array $rawDocuments;

    private string $partnerName;
    private string $quoteUuid;
    private int $quoteTypeId;

    public function __construct(array $rawDocuments, string $partnerName, string $quoteUuid, int $quoteTypeId)
    {
        $this->rawDocuments = $rawDocuments;
        $this->partnerName = $partnerName;
        $this->quoteUuid = $quoteUuid;
        $this->quoteTypeId = $quoteTypeId;
    }

    public function uniqueId(): string
    {
        return "partner-policy-document-job-{$this->quoteUuid}-{$this->quoteTypeId}";
    }

    /**
     * URLs are generated here — at execution time — so they are never expired
     * by the time Bird receives them, even under queue backlog or retries.
     */
    public function handle(): void
    {
        LoggerService::info('PartnerPolicyDocumentJob - Starting job', extra: ['partnerName' => $this->partnerName]);

        $emailData = $this->buildEmailPayload();

        $birdUrlKey = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_PARTNER_AUTOMATION_COMPLETED_WORKFLOW_URL);
        $response = app(BirdService::class)->triggerWebHookRequest($birdUrlKey, $emailData);

        LoggerService::info('PartnerPolicyDocumentJob - Job Response ', extra: ['response' => json_encode($response)]);

        if ($response?->status_code != 200) {
            LoggerService::info('PartnerPolicyDocumentJob - Job failed', extra: [
                'response' => json_encode($response),
            ]);

            return;
        }

        LoggerService::info('PartnerPolicyDocumentJob - email sent successfully');

        QuoteTag::firstOrCreate([
            'quote_type_id' => $this->quoteTypeId,
            'quote_uuid' => $this->quoteUuid,
            'name' => QuoteTagEnums::PARTNER_POLICY_DOCUMENT_SENT,
            'value' => 1,
        ]);
    }

    public function failed(\Throwable $ex): void
    {
        LoggerService::error('PartnerPolicyDocumentJob - Failed', exception: $ex);
    }

    private function buildEmailPayload(): object
    {
        $quote = CarQuote::where('uuid', $this->quoteUuid)
            ->with(['advisor', 'carMake', 'carModel', 'carModelDetail', 'insuranceProvider', 'plan.insuranceProvider'])
            ->first();

        $handBookDocuments = app(QuoteDocumentService::class)->getHandBookDocuments($quote);

        $documentTypeCodes = collect($this->rawDocuments)->pluck('document_type_code')->toArray();
        $quoteDocuments = $quote->documents()->whereIn('document_type_code', $documentTypeCodes)->latest()->get();

        $existingEmailData = (object) [
            'quoteDocuments' => $quoteDocuments,
            'handBookDocuments' => $handBookDocuments,
        ];

        return app(CentralService::class)->preparePolicyToCustomerData(
            $quote,
            QuoteTypeId::Car,
            null,
            $existingEmailData
        );
    }
}
