<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTagEnums;
use App\Models\QuoteTag;
use App\Services\BirdService;
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

    private string $partnerEmail;
    private string $quoteUuid;
    private int $quoteTypeId;

    public function __construct(array $rawDocuments, string $partnerEmail, string $quoteUuid, int $quoteTypeId)
    {
        $this->rawDocuments = $rawDocuments;
        $this->partnerEmail = $partnerEmail;
        $this->quoteUuid = $quoteUuid;
        $this->quoteTypeId = $quoteTypeId;
    }

    /**
     * Get the unique ID for the job to prevent duplicate processing.
     */
    public function uniqueId(): string
    {
        return "partner-policy-document-job-{$this->quoteUuid}-{$this->quoteTypeId}";
    }

    /**
     * Execute the job.
     *
     * URLs are generated here — at execution time — so they are never expired
     * by the time Bird receives them, even under queue backlog or retries.
     */
    public function handle(QuoteDocumentService $quoteDocumentService): void
    {
        LoggerService::info('PartnerPolicyDocumentJob - Starting job', extra: ['partnerEmail' => $this->partnerEmail]);

        $documentPayload = $this->buildDocumentPayload($quoteDocumentService);

        $emailData = (object) [
            'partnerEmail' => $this->partnerEmail,
            ...$documentPayload,
        ];

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

    /**
     * Generate fresh temporary Azure URLs at job execution time.
     *
     * @return array<string, string>
     */
    private function buildDocumentPayload(QuoteDocumentService $quoteDocumentService): array
    {
        $payload = [];

        foreach ($this->rawDocuments as $document) {
            $docUrl = $document['watermarked_doc_url'] ?? $document['doc_url'];
            $payload[$document['document_type_code']] = $quoteDocumentService->getDocumentUrl($docUrl, 'azureIMPrivate') ?? '';
            $payload['EXT_'.$document['document_type_code']] = $quoteDocumentService->getDocumentExtension($docUrl) ?? '';
        }

        return $payload;
    }
}
