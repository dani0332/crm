<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DTO\EpBookingContext;
use App\Enums\ApplicationStorageEnums;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteDocumentsEnum;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Models\ApplicationStorage;
use App\Models\EmbeddedProduct;
use App\Models\EmbeddedTransaction;
use App\Services\BirdService;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class EpSendDocumentJob implements ShouldQueue
{
    use Queueable, GenericQueriesAllLobs;

    public $tries = 3;
    public $timeout = 180;
    public $backoff = 45;
    
    private string $logPrefix = 'EpSendDocument - Job:';
    private array $logExtra = [];

    public mixed $quote = null;
    private string $storageBaseUrl = '';

    public function __construct(
        public EpBookingContext $context
    ) {
        $quoteType = QuoteTypes::getName($this->context->quoteTypeId)->value;
        $this->quote = $this->getQuoteObject($quoteType, $this->context->quoteId);
    }

    public function handle(): void
    {
        // Start feature and quote logging
        LoggerService::startQuoteLogging($this->context->quoteUUID, LoggerFeatureEnum::EP_PROCESS_SEND_DOCUMENT);

        $this->storageBaseUrl = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';

        LoggerService::info("{$this->logPrefix} Starting");
        
        $this->sendEmail();

        LoggerService::info("{$this->logPrefix} Completed");
    }

    public function failed(Throwable $exception): void
    {
        LoggerService::error("{$this->logPrefix} Failed", extra: [
            'error' => $exception->getMessage()
        ]);
    }

    public function shouldRetry(Throwable $exception): bool
    {
        // Retry for email/notification service issues only
        return str_contains($exception->getMessage(), 'mail') ||
               str_contains($exception->getMessage(), 'notification') ||
               str_contains($exception->getMessage(), 'smtp') ||
               str_contains($exception->getMessage(), 'connection');
    }

    
    /**
     * This function use to send email
     *
     * @param  CarQuote  $carQuote
     * @param  int  $emailTemplateId
     * @param  object  $emailData
     * @return int
     */
    private function sendEmail()
    {
        LoggerService::info("{$this->logPrefix} Email sending for uuid: {$this->quote->uuid}");

        $advisor = $this->quote?->advisor;
        $recipients = [
            "to" => [$this->quote?->email],
            "cc" => ["arsalanmughal23@yopmail.com"],
            "bcc" => []
        ];
        $advisorData = [
            'advisorEmail' => $advisor?->email,
            'advisorLandLine' => $advisor?->landline_no,
            'advisorMobileNoWithoutSpaces' => removeSpaces($advisor?->mobile_no ?? ''),
            'advisorMobilePhone' => $advisor?->mobile_no,
            'advisorName' => $advisor?->name,
            'advisorProfilePhotoPath' => $advisor?->profile_photo_path,
        ];
        $policyContext = [
            "policyClaimLimit" => "One claim per policy term.",
            "policyCoverage" => "If you have an accident, you pay part of the repair bill (this is called 'excess'), usually between AED 350 to AED 1,400. This benefit gives you back up to AED 1,200.",
            "policyDuration" => "Your coverage lasts for 13 months or until the expiry of your motor insurance policy, whichever comes first.",
        ];

        $emailData = [
            "Attachments" => $this->fetchAttachments(),
            "Tags" => WorkflowTypeEnum::SEND_EP_ECB_POLICY_DOCUMENTS_EMAIL,
            "customerName" => $this->quote?->first_name . ' ' . $this->quote?->last_name,
            "refID" => $this->quote?->code,
            ...$recipients,
            ...$advisorData,
            "attachingDocsEmail" => "yes",
            "DisplayName" => "InsuranceMarket.ae",
            "supportUserEmail" => "arsalansupport23@yopmail.com",
            ...$policyContext,
        ];

        $this->triggerBirdWorkflow($emailData);
    }

    /**
     * This function use to trigger bird workflow
     *
     * @param  array  $birdEmailData
     */
    private function triggerBirdWorkflow(array $birdEmailData)
    {
        $sendEpDocumentsEvent = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_SENT_EP_POLICY_DOCUMENTS_EMAIL)->first();
        LoggerService::info('SendEpDocuments Email: ', extra: $birdEmailData);

        $url = $sendEpDocumentsEvent->value ?? 'https://api.bird.com/workspaces/a1b37cbd-b29d-4371-a81a-c1cd939b73a2/flows/f25be3f7-9382-426d-aa90-9f9aaa1825dd/invoke-sync';
        app(BirdService::class)->triggerWebHookRequest($url, (object) $birdEmailData);
    }

    
    public function fetchAttachments()
    {
        $transaction = EmbeddedTransaction::findOrFail($this->context->etId);
        $embeddedProduct = $transaction->product->embeddedProduct;

        $watermarkedDocuments = $transaction->documents()
            ->whereIn('document_type_code', QuoteDocumentsEnum::getSukoonInitialDocTypes())->get()
            ->where('is_watermarked', true);

        $watermarkedDocumentTypes = $watermarkedDocuments->pluck('document_type_code')->toArray();
        $missingReqWatermarkedDocTypes = array_diff(QuoteDocumentsEnum::getSukoonInitialDocTypes(), $watermarkedDocumentTypes);

        // make sure email required watermarked documents is not missing
        if (! empty($missingReqWatermarkedDocTypes)) {
            return ['success' => false, 'message' => 'Required watermarked document is not found'];
        }

        $attachments = $this->fetchPolicyWordings($embeddedProduct);

        foreach ($watermarkedDocuments as $document) {
            $attachments[] = [
                'fileUrl' => $this->storageBaseUrl.$document->watermarked_doc_url,
                'fileName' => $document->original_name,
            ];
        }

        return $attachments;
    }

    public function fetchPolicyWordings(EmbeddedProduct $embeddedProduct)
    {
        $attachments = [];
        $documents = json_decode($embeddedProduct->company_documents ?? false);
        if (! empty($documents)) {
            foreach ($documents as $item) {
                $policyWordingsUrl = $item->path !== '' ? $this->storageBaseUrl.$item->path : '';
                if (! empty($item->path)) {
                    $attachments[] = [
                        'fileUrl' => $policyWordingsUrl,
                        'fileName' => $embeddedProduct->display_name.' - Policy Wordings.pdf'
                    ];
                }
            }
        }
        return $attachments;
    }
}
