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

    private function getEpConfigurations()
    {        
        $epEcbAppStorageKeys = [
            ApplicationStorageEnums::BIRD_SENT_EP_POLICY_DOCUMENTS_EMAIL,
            ApplicationStorageEnums::SENT_EP_ECB_POLICY_DOCUMENTS_EMAIL_SUPPORT_USER,
            ApplicationStorageEnums::SENT_EP_ECB_POLICY_DOCUMENTS_EMAIL_BCC,
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
            throw new \Error('EP ECB configuration not found');
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
        $supportUserEmail = $this->epEcbConfiguration[ApplicationStorageEnums::SENT_EP_ECB_POLICY_DOCUMENTS_EMAIL_SUPPORT_USER] ?? '';

        $emailData = [
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
            'supportUserEmail' => $supportUserEmail,
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
        $bccEmails = $this->epEcbConfiguration[ApplicationStorageEnums::SENT_EP_ECB_POLICY_DOCUMENTS_EMAIL_BCC] ?? [];
        return [
            'to' => empty($customerEmail) ? [] : [$customerEmail],
            'cc' => empty($advisorEmail) ? [] : [$advisorEmail],
            'bcc' => $bccEmails,
        ];
    }

    /**
     * This function use to trigger bird workflow
     */
    private function triggerBirdWorkflow(array $birdEmailData)
    {
        $birdWorkflowUrl = $this->epEcbConfiguration[ApplicationStorageEnums::BIRD_SENT_EP_POLICY_DOCUMENTS_EMAIL] ?? '';
        app(BirdService::class)->triggerWebHookRequest($birdWorkflowUrl, (object) $birdEmailData);
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
