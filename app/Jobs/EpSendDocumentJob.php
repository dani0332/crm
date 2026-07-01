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
use App\Services\EmailServices\WebEngageService;
use App\Services\Logger\LoggerService;
use App\Services\QuoteDocumentService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Str;
use Throwable;

class EpSendDocumentJob implements ShouldQueue
{
    use GenericQueriesAllLobs, Queueable;

    public $timeout = 120;
    private string $logPrefix = 'EpSendDocument - Job:';
    private array $logExtra = [];
    public mixed $quote = null;
    private string $storageBaseUrl = '';
    private array $epEcbConfiguration = [];

    public function __construct(
        public EpBookingContext $context
    ) {}

    public function handle(): void
    {
        $quoteType = QuoteTypes::getName($this->context->quoteTypeId)->value;
        $this->quote = $this->getQuoteObject($quoteType, $this->context->quoteId);

        if (! $this->quote) {
            throw new \Exception('Quote not found for sending email');
        }

        // Start feature and quote logging
        LoggerService::startQuoteLogging($this->quote?->code, LoggerFeatureEnum::EP_PROCESS_SEND_DOCUMENT);
        LoggerService::info("{$this->logPrefix} Starting");

        $this->getEpConfigurations();

        $this->sendEmail();

        LoggerService::info("{$this->logPrefix} Completed");
    }

    public function failed(Throwable $exception): void
    {
        LoggerService::error("{$this->logPrefix} Failed", extra: [
            'error' => $exception->getMessage(),
        ]);
    }

    private function getEpConfigurations(): void
    {
        $epEcbAppStorageKeys = [
            ApplicationStorageEnums::BIRD_EP_WORKFLOW_URL,
            ApplicationStorageEnums::SENT_EP_ECB_POLICY_DOCUMENTS_EMAIL_CC,
            ApplicationStorageEnums::EP_ECB_POLICY_CLAIM_LIMIT,
            ApplicationStorageEnums::EP_ECB_POLICY_COVERAGE,
            ApplicationStorageEnums::EP_ECB_POLICY_DURATION,
        ];
        $appStorageRecords = ApplicationStorage::select('value', 'key_name')
            ->where('is_active', ApplicationStorageEnums::ACTIVE)
            ->whereIn('key_name', $epEcbAppStorageKeys)
            ->whereNotNull('value')
            ->get();

        $missingAppStorageKeys = array_diff($epEcbAppStorageKeys, $appStorageRecords->pluck('key_name')->toArray());

        $this->storageBaseUrl = storageUrl();
        if (empty($this->storageBaseUrl) || count($missingAppStorageKeys) > 0) {
            throw new \Exception('EP ECB configuration not found');
        }

        $this->epEcbConfiguration = $appStorageRecords->pluck('value', 'key_name')->toArray();
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
                ->expireAfter(120),
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
        LoggerService::info("{$this->logPrefix} Email sending for code: {$this->quote?->code}");

        $advisor = $this->quote?->advisor;

        $policyContext = $this->getPolicyContext();
        $recipients = $this->getRecipients($this->quote->email ?? '', $advisor->email ?? '');
        $advisorData = $this->getAdvisorData($advisor);
        $attachments = $this->fetchAttachments();

        $emailData = [
            'uniqueId' => (string) Str::ulid(),
            'Attachments' => $attachments,
            'Tags' => WorkflowTypeEnum::SEND_EP_ECB_POLICY_DOCUMENTS_EMAIL,
            'workflowType' => WorkflowTypeEnum::SEND_EP_ECB_POLICY_DOCUMENTS_EMAIL,
            'customerName' => trim(($this->quote?->first_name ?? '').' '.($this->quote?->last_name ?? '')),
            'refID' => $this->quote?->code ?? '',
            'uuid' => $this->quote?->uuid ?? '',
            ...$recipients,
            ...$advisorData,
            'attachingDocsEmail' => count($attachments) > 0 ? 'yes' : 'no',
            'DisplayName' => 'InsuranceMarket.ae',
            'customerId' => $this->quote->customer_id,
            'customerEmail' => $this->quote->email,
            'firstName' => $this->quote->first_name ?? '',
            'lastName' => $this->quote->last_name ?? '',
            'customerMobile' => (! empty($lead->mobile_no) ? $lead->mobile_no : ''),
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
        return [
            'policyClaimLimit' => $this->epEcbConfiguration[ApplicationStorageEnums::EP_ECB_POLICY_CLAIM_LIMIT] ?? '',
            'policyCoverage' => $this->epEcbConfiguration[ApplicationStorageEnums::EP_ECB_POLICY_COVERAGE] ?? '',
            'policyDuration' => $this->epEcbConfiguration[ApplicationStorageEnums::EP_ECB_POLICY_DURATION] ?? '',
        ];
    }

    private function getRecipients(string $customerEmail, string $advisorEmail): array
    {
        $ccEmail = $this->epEcbConfiguration[ApplicationStorageEnums::SENT_EP_ECB_POLICY_DOCUMENTS_EMAIL_CC] ?? '';

        $ccEmails = [];
        if (! empty($ccEmail)) {
            $ccEmails[] = $ccEmail;
        }

        if (! empty($advisorEmail)) {
            $ccEmails[] = $advisorEmail;
        }

        return [
            'customerEmail' => $customerEmail,
            'ccEmails' => $ccEmails,
        ];
    }

    /**
     * This function use to trigger bird workflow
     */
    private function triggerBirdWorkflow(array $birdEmailData)
    {

        LoggerService::info("{$this->logPrefix} triggerBirdWorkflow: ", extra: ['data' => $birdEmailData]);

        app(WebEngageService::class)->sendEvent(WorkflowTypeEnum::SEND_EP_ECB_POLICY_DOCUMENTS_EMAIL, (array) $birdEmailData);
    }

    public function fetchAttachments()
    {
        $transaction = EmbeddedTransaction::findOrFail($this->context->etId);
        $embeddedProduct = $transaction?->product?->embeddedProduct;

        if (empty($embeddedProduct)) {
            throw new \Exception("Embedded product not found for transaction ID: {$this->context->etId}");
        }

        $epSentToCustomerDocTypeCodes = QuoteDocumentsEnum::getEpSentToCustomerDocTypes();
        $watermarkedDocuments = $transaction->documents()
            ->whereIn('document_type_code', $epSentToCustomerDocTypeCodes)->get()
            ->where('is_watermarked', true);

        $watermarkedDocumentTypes = $watermarkedDocuments->pluck('document_type_code')->toArray();
        $missingReqWatermarkedDocTypes = array_diff($epSentToCustomerDocTypeCodes, $watermarkedDocumentTypes);

        // make sure email required watermarked documents is not missing
        if (! empty($missingReqWatermarkedDocTypes)) {
            throw new \Exception('Required watermarked document is not found');
        }

        $attachments = $this->fetchPolicyWordings($embeddedProduct);

        foreach ($watermarkedDocuments as $document) {
            $documentUrl = app(QuoteDocumentService::class)->getDocumentUrl(
                $document->watermarked_doc_url,
                'azureIMPrivate',
            );

            if ($documentUrl) {
                $attachments[] = [
                    'fileUrl' => $documentUrl,
                    'fileName' => $document->original_name,
                ];
            }
        }

        return $attachments;
    }

    public function fetchPolicyWordings(EmbeddedProduct $embeddedProduct)
    {
        $attachments = [];
        $documents = json_decode($embeddedProduct->company_documents ?? '[]', true);
        if (! empty($documents)) {
            foreach ($documents as $item) {
                $policyWordingsUrl = isset($item['path']) && $item['path'] !== '' ? $this->storageBaseUrl.$item['path'] : '';
                if (! empty($item['path'])) {
                    $attachments[] = [
                        'fileUrl' => $policyWordingsUrl,
                        'fileName' => $embeddedProduct->display_name.' - Policy Wordings.pdf',
                    ];
                }
            }
        }

        return $attachments;
    }
}
