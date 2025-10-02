<?php

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
use App\Models\User;
use App\Services\BirdService;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

class EpSendDocumentJob implements ShouldQueue
{
    use Queueable, GenericQueriesAllLobs;

    public $timeout = 180;
    
    private string $logPrefix = 'EpSendDocument - Job:';
    private array $logExtra = [];

    public mixed $quote = null;
    private string $storageBaseUrl = '';

    public function __construct(
        public EpBookingContext $context
    ) {}

    public function handle(): void
    {       
        $quoteType = QuoteTypes::getName($this->context->quoteTypeId)->value;
        $this->quote = $this->getQuoteObject($quoteType, $this->context->quoteId); 

        if (!$this->quote) {
            throw new \Exception("Quote not found for sending email");
        }

        // Start feature and quote logging
        LoggerService::startQuoteLogging($this->quote?->code, LoggerFeatureEnum::EP_PROCESS_SEND_DOCUMENT);

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
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        $lockKey = "ep-send-document-{$this->context->etId}-{$this->context->quoteCode}";

        return [
            (new WithoutOverlapping($lockKey))
                ->dontRelease()
                ->expireAfter(180)
        ];
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
        
        $policyContext = $this->getPolicyContext();
        $recipients = $this->getRecipients($this->quote->email ?? '', $advisor->email ?? '');
        $advisorData = $this->getAdvisorData($advisor);
        $attachments = $this->fetchAttachments();

        $emailData = [
            "Attachments" => $attachments,
            "Tags" => WorkflowTypeEnum::SEND_EP_ECB_POLICY_DOCUMENTS_EMAIL,
            "customerName" => trim(($this->quote?->first_name ?? '') . ' ' . ($this->quote?->last_name ?? '')),
            "refID" => $this->quote?->code ?? '',
            "uuid" => $this->quote?->uuid ?? '',
            ...$recipients,
            ...$advisorData,
            "attachingDocsEmail" => count($attachments) > 0 ? "yes" : "no",
            "DisplayName" => "InsuranceMarket.ae",
            "supportUserEmail" => "arsalansupport23@yopmail.com",
            ...$policyContext,
        ];

        $this->triggerBirdWorkflow($emailData);
    }

    private function getAdvisorData(User $advisor): array
    {
        return [
            'advisorEmail' => $advisor?->email,
            'advisorLandLine' => $advisor?->landline_no,
            'advisorMobileNoWithoutSpaces' => removeSpaces($advisor?->mobile_no ?? ''),
            'advisorMobilePhone' => $advisor?->mobile_no,
            'advisorName' => $advisor?->name,
            'advisorProfilePhotoPath' => $advisor?->profile_photo_path,
        ];
    }

    private function getPolicyContext(): array
    {
        $policyContext = config('embedded-products.ecb.policy_context');

        return [
            'policyClaimLimit' => $policyContext['policy_claim_limit'] ?? '',
            'policyCoverage' => $policyContext['policy_coverage'] ?? '',
            'policyDuration' => $policyContext['policy_duration'] ?? '',
        ];
    }

    private function getRecipients(string $customerEmail, string $advisorEmail): array
    {
        $configEnv = app()->environment('production') ? 'prod' : 'non_prod';
        $recipientEmails = config("embedded-products.ecb.{$configEnv}.recipient_emails");

        $toEmails = $recipientEmails['to'];
        if(!empty($customerEmail)) {
            $toEmails[] = $customerEmail;
        }

        $ccEmails = $recipientEmails['cc'];
        if(!empty($advisorEmail)) {
            $ccEmails[] = $advisorEmail;
        }

        $recipients = [
            "to" => $toEmails,
            "cc" => $ccEmails,
            "bcc" => $recipientEmails['bcc']
        ];

        return $recipients;
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

        $url = $sendEpDocumentsEvent->value;
        app(BirdService::class)->triggerWebHookRequest($url, (object) $birdEmailData);
    }

    
    public function fetchAttachments()
    {
        $transaction = EmbeddedTransaction::findOrFail($this->context->etId);
        $embeddedProduct = $transaction?->product?->embeddedProduct;

        if (empty($embeddedProduct)) {
            throw new \Exception("Embedded product not found for transaction ID: {$this->context->etId}");
        }

        $watermarkedDocuments = $transaction->documents()
            ->whereIn('document_type_code', QuoteDocumentsEnum::getSukoonInitialDocTypes())->get()
            ->where('is_watermarked', true);

        $watermarkedDocumentTypes = $watermarkedDocuments->pluck('document_type_code')->toArray();
        $missingReqWatermarkedDocTypes = array_diff(QuoteDocumentsEnum::getSukoonInitialDocTypes(), $watermarkedDocumentTypes);

        // make sure email required watermarked documents is not missing
        if (! empty($missingReqWatermarkedDocTypes)) {
            throw new \Exception('Required watermarked document is not found');
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
