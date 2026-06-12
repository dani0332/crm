<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\EmbeddedProductEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteDocumentsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\WorkflowTypeEnum;
use App\Exceptions\EmbeddedProductDocumentSendFailedException;
use App\Exceptions\EmbeddedProductDocumentUploadFailedException;
use App\Jobs\WatermarkDocumentsJob;
use App\Models\CarQuote;
use App\Models\DocumentType;
use App\Models\EmbeddedProduct;
use App\Models\EmbeddedTransaction;
use App\Models\PersonalQuote;
use App\Models\QuoteDocument;
use App\Repositories\EmbeddedProductRepository;
use App\Repositories\EmbeddedTransactionRepository;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmbeddedTransactionService extends BaseService
{
    use GenericQueriesAllLobs;

    /**
     * Create a new class instance.
     */
    public function __construct(
        protected EmbeddedTransactionRepository $embeddedTransactionRepo,
        protected EmbeddedProductRepository $embeddedProductRepo,
        protected BirdService $birdService,
        protected SendEmailCustomerService $sendEmailCustomerService,
    ) {
        parent::__construct();
    }

    public function isRetargetingEpReminderEnabled(): bool
    {
        return (bool) getAppStorageValueByKey(ApplicationStorageEnums::ENABLE_CAR_EP_RETARGETING_REMINDER);
    }

    public function retargetEpReminder(CarQuote|PersonalQuote $quote, int $quoteTypeId)
    {
        $epTransactions = $this->embeddedTransactionRepo
            ->fetchFilterEpTransactions(
                $quote->id,
                $quoteTypeId,
                isActive: true,
                paymentStatusId: PaymentStatusEnum::DRAFT,
                quoteStatusId: QuoteStatusEnum::PolicyBooked,
                epShortCode: EmbeddedProductEnum::CAR_EP_RETARGETING_REMINDER_ALLOWED_EPS,
            );

        if ($epTransactions->isEmpty()) {
            LoggerService::info('retargetEpReminder: Record not found', extra: ['quoteId' => $quote->id, 'quoteTypeId' => $quoteTypeId]);

            return [];
        }

        $response = [];
        foreach ($epTransactions as $epTransaction) {
            try {
                if ($epTransaction->quote_type_id === QuoteTypeId::Bike) {
                    $result = $this->triggerEpRetargetingWorkflowForBike($quote, $quoteTypeId, $epTransaction);
                } else {
                    $result = $this->triggerBirdWorkflowRetargetEpReminder($quote, $quoteTypeId, $epTransaction);
                }
                $response[] = ['embeddedTransactionCode' => $epTransaction->code, 'status_code' => $result->status_code, 'message' => $result->message ?? ''];
            } catch (\Throwable $e) {
                LoggerService::error('retargetEpReminder: Bird workflow request failed for transaction', extra: [
                    'embeddedTransactionCode' => $epTransaction->code,
                    'quoteId' => $quote->id,
                    'error' => $e->getMessage(),
                ]);
                $response[] = [
                    'embeddedTransactionCode' => $epTransaction->code,
                    'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => $e->getMessage(),
                ];

                continue;
            }
        }

        LoggerService::info('retargetEpReminder: Retargeting EP Reminder processing completed', extra: ['response' => $response]);

        return $response;
    }

    /**
     * This function use to trigger bird workflow
     */
    protected function triggerBirdWorkflowRetargetEpReminder(CarQuote $quote, int $quoteTypeId, EmbeddedTransaction $epTransaction)
    {
        $birdWorkflowUrl = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_EP_WORKFLOW_URL, useCache: true);
        if (empty($birdWorkflowUrl)) {
            return (object) ['status_code' => Response::HTTP_NOT_FOUND, 'message' => 'Bird EP Reminder Workflow URL not found'];
        }

        $getRetargetingEpReminderUrl = route('get.ep-workflow-data', [
            'quoteId' => $quote->id,
            'quoteTypeId' => $quoteTypeId,
            'embeddedTransactionCode' => $epTransaction->code,
        ]);

        $birdEmailData = [
            'quoteId' => $quote->id,
            'quoteTypeId' => $quoteTypeId,
            'refId' => $quote->code,
            'uuid' => $quote->uuid,
            'embeddedTransactionCode' => $epTransaction->code,
            'workflowType' => WorkflowTypeEnum::CAR_EP_RETARGETING_REMINDER,
            'getRetargetingEpReminderUrl' => $getRetargetingEpReminderUrl,
            'reminderNumber' => 1,
        ];

        LoggerService::info('triggerBirdWorkflowRetargetEpReminder: ', extra: ['data' => $birdEmailData]);

        return $this->birdService->triggerWebHookRequest($birdWorkflowUrl, (object) $birdEmailData);
    }

    protected function triggerEpRetargetingWorkflowForBike(PersonalQuote $quote, int $quoteTypeId, EmbeddedTransaction $epTransaction): object
    {
        $eventName = getAppStorageValueByKey(ApplicationStorageEnums::BREVO_BIKE_EP_RETARGETING_EVENT_NAME);

        if (empty($eventName)) {
            LoggerService::info('triggerEpRetargetingWorkflowForBike: Brevo configuration missing', extra: [
                'hasEventName' => ! empty($eventName),
            ]);

            return (object) ['status_code' => Response::HTTP_NOT_FOUND, 'message' => 'Brevo Bike EP retargeting workflow not configured'];
        }

        $emailData = [
            'quoteId' => $quote->id,
            'quoteTypeId' => $quoteTypeId,
            'embeddedTransactionCode' => $epTransaction->code,
        ];

        $apiResponse = SIBService::createWorkflowEvent($eventName, $quote, [], $emailData);

        return (object) ['status_code' => $apiResponse, 'message' => $apiResponse == Response::HTTP_OK ? 'Bike EP retargeting workflow triggered' : 'Failed to trigger bike EP retargeting workflow'];
    }

    public function triggerRetargetingEpReminderForBike(int $quoteId, int $quoteTypeId, string $embeddedTransactionCode): object
    {
        $quote = PersonalQuote::with('bikeQuote', 'advisor')->find($quoteId);
        if (empty($quote)) {
            return (object) ['status_code' => Response::HTTP_NOT_FOUND, 'message' => 'Quote not found'];
        }
        $bikeQuote = $quote->bikeQuote;
        if (empty($bikeQuote)) {
            return (object) ['status_code' => Response::HTTP_NOT_FOUND, 'message' => 'Bike quote not found'];
        }
        $epTransaction = $this->embeddedTransactionRepo->fetchFindEmbededTransactionWithDetails(
            $quoteId,
            $quoteTypeId,
            $embeddedTransactionCode,
            isActive: true,
            paymentStatusId: PaymentStatusEnum::DRAFT,
            quoteStatusId: QuoteStatusEnum::PolicyBooked,
        );

        $templateId = getAppStorageValueByKey(ApplicationStorageEnums::RDX_EP_RETARGETING_REMINDER_TEMPLATE);

        if (empty($templateId)) {
            LoggerService::info('sendBikeEpRetargetingEmail: Template ID not configured');

            return (object) ['status_code' => Response::HTTP_NOT_FOUND, 'message' => 'Bike EP retargeting template not configured'];
        }

        $emailData = [
            'templateId' => $templateId,
            'quoteId' => $quote->id,
            'quoteTypeId' => $quoteTypeId,
            'refId' => $quote->code,
            'uuid' => $quote->uuid,
            'embeddedTransactionCode' => $epTransaction->code,
            'customerId' => $quote->customer_id,
            'customerEmail' => $quote->email,
            'customerName' => $quote->first_name.' '.$quote->last_name,
            'vehicleName' => $bikeQuote->bikeMake?->text.' '.$bikeQuote->bikeModel?->text,
            'buyNowUrl' => config('constants.ECOM_BIKE_INSURANCE_QUOTE_URL').$quote->uuid.'/payment/?'.http_build_query([
                'planId' => $bikeQuote->plans?->id,
                'providerCode' => $bikeQuote->plans?->insuranceProvider?->code,
                'selectEpShortCode' => $epTransaction->product?->embeddedProduct?->short_code,
            ]),
            'advisor' => [
                'email' => $quote->advisor?->email ?? null,
                'name' => $quote->advisor?->name ?? null,
            ],
        ];

        LoggerService::info('triggerRetargetingEpReminderForBike: ', extra: ['data' => $emailData]);

        return $this->sendBikeEpRetargetingEmail($emailData, (int) $templateId);
    }

    public function handleTriggerEpRetargetingEmail(int $quoteId, int $quoteTypeId, string $embeddedTransactionCode): object
    {
        $isTriggerAllowed = $this->getEpRetargetingReminderData($quoteId, $quoteTypeId, $embeddedTransactionCode)?->getStatusCode() === Response::HTTP_OK;

        if (! $isTriggerAllowed) {
            return (object) ['status_code' => Response::HTTP_OK, 'message' => 'Criteria not met for triggering the email.'];
        }

        LoggerService::info("Trigger EP Retargeting Reminder Email - Quote ID: {$quoteId}, Quote Type ID: {$quoteTypeId}, Embedded Transaction Code: {$embeddedTransactionCode}");

        return $this->triggerRetargetingEpReminderForBike($quoteId, $quoteTypeId, $embeddedTransactionCode);
    }

    protected function sendBikeEpRetargetingEmail(array $emailData, int $templateId): object
    {
        $responseCode = $this->sendEmailCustomerService->sendBikeEpRetargetingEmail(
            $templateId,
            $emailData,
            'bike-ep-retargeting',
        );

        $isSuccess = $responseCode === Response::HTTP_CREATED || $responseCode === Response::HTTP_OK;

        LoggerService::info('sendBikeEpRetargetingEmail: completed', extra: [
            'customerEmail' => $emailData['customerEmail'],
            'refId' => $emailData['refId'],
            'responseCode' => $responseCode,
        ]);

        return (object) [
            'status_code' => $isSuccess ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $isSuccess ? 'Bike EP retargeting email sent' : 'Failed to send bike EP retargeting email',
        ];
    }

    public function getEpRetargetingReminderData(int $quoteId, int $quoteTypeId, string $embeddedTransactionCode): JsonResponse
    {
        $embeddedTransaction = $this->embeddedTransactionRepo
            ->fetchFindEmbededTransactionWithDetails(
                $quoteId,
                $quoteTypeId,
                $embeddedTransactionCode,
                isActive: true,
                paymentStatusId: PaymentStatusEnum::DRAFT,
                quoteStatusId: QuoteStatusEnum::PolicyBooked,
            );
        $quote = $embeddedTransaction?->quoteRequest ?? null;

        if (empty($embeddedTransaction) || empty($quote)) {
            return apiResponse(null, Response::HTTP_NOT_FOUND, 'Record not found');
        }
        $isBike = $quoteTypeId === QuoteTypeId::Bike;

        $carMake = $quote->carMake?->text ?? $quote->bikeQuote?->bikeMake?->text ?? null;
        $carModel = $quote->carModel?->text ?? $quote->bikeQuote?->bikeModel?->text ?? null;
        $epShortCode = $embeddedTransaction->product?->embeddedProduct?->short_code ?? null;
        $planId = $quote->plan?->id ?? $quote->carPlan?->id ?? null;
        $providerCode = $quote->plan?->insuranceProvider?->code ?? $quote->carPlan?->insuranceProvider?->code ?? null;

        $isEpShortCodeAllowedForReminder = in_array($epShortCode, EmbeddedProductEnum::CAR_EP_RETARGETING_REMINDER_ALLOWED_EPS, true);

        if (! $isEpShortCodeAllowedForReminder) {
            LoggerService::info('getEpRetargetingReminderData: Reminder is not allowed for this EP short code', extra: [
                'epShortCode' => $epShortCode,
            ]);

            return apiResponse(null, Response::HTTP_BAD_REQUEST, 'Not eligible for reminder');
        }

        $epStrategy = $this->embeddedProductRepo->createStrategy($epShortCode);
        $isEpDisabled = $epStrategy->isDisabled($embeddedTransaction);

        if (empty($quote->email) || empty($planId) || empty($providerCode) || $isEpDisabled) {
            LoggerService::info('getEpRetargetingReminderData: Not eligible for reminder', extra: [
                'vehicleName' => "{$carMake}-{$carModel}",
                'quote-email' => $quote->email,
                'epShortCode' => $epShortCode,
                'providerCode' => $providerCode,
                'planId' => $planId,
                'isEpDisabled' => $isEpDisabled,
            ]);

            return apiResponse(null, Response::HTTP_BAD_REQUEST, 'Not eligible for reminder');
        }

        $ecomBaseUrl = $isBike ? config('constants.ECOM_BIKE_INSURANCE_QUOTE_URL') : config('constants.ECOM_CAR_INSURANCE_QUOTE_URL');

        $buyNowUrl = $ecomBaseUrl.$quote->uuid
            .'/payment/?'.http_build_query([
                'planId' => $planId,
                'providerCode' => $providerCode,
                'selectEpShortCode' => $epShortCode,
            ]);

        $data = [
            'quote' => $quote->only(['id', 'uuid', 'quote_status_id', 'policy_booking_date']),
            'embeddedTransaction' => $embeddedTransaction->only(['id', 'code', 'quote_type_id', 'quote_request_id', 'quote_request_type', 'is_selected', 'payment_status_id', 'product_id']),
            'emailWorkflowData' => [
                'customerEmail' => $quote->email,
                'customerName' => $quote->full_name,
                'advisorEmail' => $quote->advisor?->email ?? null,
                'buyNowUrl' => $buyNowUrl,
                'epShortCode' => $epShortCode,
                'vehicleMake' => $carMake,
                'vehicleModel' => $carModel,
            ],
        ];

        return apiResponse($data, Response::HTTP_OK, 'Retargeting EP Reminder data');
    }

    /**
     * Replaces an embedded-product quote document (manual override), including storage upload and optional watermark dispatch.
     *
     * Coerces `epId`, `quoteId`, and `documentId` to int: multipart and form-encoded bodies often keep validated numeric fields as strings, which would otherwise violate strict typing when passed to int-hinted helpers (including queued closures).
     */
    public function updateEpDocument(array $data): bool
    {
        $data['epId'] = (int) $data['epId'];
        $data['quoteId'] = (int) $data['quoteId'];
        $data['documentId'] = (int) $data['documentId'];

        $ctx = $this->resolveUpdateEpDocumentContext($data);
        if ($ctx === null) {
            return false;
        }

        $embeddedTransaction = $ctx['embeddedTransaction'];
        $oldDocument = $ctx['oldDocument'];
        $documentType = $ctx['documentType'];
        $quoteObject = $ctx['quoteObject'];
        $storedDocName = $ctx['storedDocName'];

        $uploadedFile = $data['file'];
        $originalName = $uploadedFile->getClientOriginalName();
        $uniqueBlobName = $this->embeddedProductRepo->uniqueBlobNameFromOriginalName($originalName);
        $azureObjectName = $quoteObject->uuid.'_'.$uniqueBlobName;
        $docUuid = uniqid();

        $filePathAzure = $uploadedFile->storeAs(
            EmbeddedProductRepository::DOCUMENTS_STORAGE_PREFIX.$documentType->folder_path,
            $azureObjectName,
            'azureIMPrivate'
        );

        if ($filePathAzure === false) {
            throw new EmbeddedProductDocumentUploadFailedException(EmbeddedProductRepository::ERROR_UPLOADING_DOCUMENT);
        }

        DB::transaction(function () use (
            $embeddedTransaction,
            $oldDocument,
            $storedDocName,
            $originalName,
            $filePathAzure,
            $documentType,
            $docUuid,
            $data,
            $quoteObject
        ): void {
            $embeddedTransaction->save();
            $oldDocument->delete();

            $this->persistManualOverrideEpDocument([
                'embeddedTransaction' => $embeddedTransaction,
                'storedDocName' => $storedDocName,
                'originalName' => $originalName,
                'filePathAzure' => $filePathAzure,
                'documentType' => $documentType,
                'docUuid' => $docUuid,
                'remarks' => $data['remarks'],
                'quoteObject' => $quoteObject,
                'epId' => $data['epId'],
                'modelType' => $data['modelType'],
            ]);
        });

        return true;
    }

    /**
     * Resolves models and derived names for EP document replacement, or null when prerequisites fail.
     *
     * @return array{
     *     embeddedTransaction: EmbeddedTransaction,
     *     oldDocument: QuoteDocument,
     *     documentType: DocumentType,
     *     quoteObject: object,
     *     storedDocName: string,
     * }|null
     */
    private function resolveUpdateEpDocumentContext(array $data): ?array
    {
        $ep = EmbeddedProduct::query()->with('prices')->where('id', $data['epId'])->first();
        $transaction = $ep !== null
            ? $this->embeddedProductRepo->fetchTransaction($data['modelType'], $data['quoteId'], $ep, false)
            : null;

        $embeddedTransaction = ($transaction !== null && $transaction->isNotEmpty())
            ? $transaction->first()
            : null;

        $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($data['modelType']));

        $oldDocument = $embeddedTransaction !== null
            ? $embeddedTransaction->documents()
                ->withoutTrashed()
                ->with([
                    'documentType' => function ($query) use ($quoteTypeId): void {
                        $query->where('quote_type_id', $quoteTypeId);
                    },
                ])
                ->find($data['documentId'])
            : null;

        $documentType = $oldDocument?->documentType;

        $quoteObject = $documentType !== null
            ? $this->getQuoteObject($data['modelType'], $data['quoteId'])
            : false;

        if ($ep === null
            || $transaction === null
            || $transaction->isEmpty()
            || $oldDocument === null
            || $documentType === null
            || $quoteObject === false
        ) {
            return null;
        }

        match ($oldDocument->document_type_code) {
            QuoteDocumentsEnum::POLICY_SCHEDULE => $embeddedTransaction->certificate_number = $data['documentNumber'],
            QuoteDocumentsEnum::CAR_TAX_INVOICE_RAISE_BY_BUYER => $embeddedTransaction->tax_invoice_buyer_no = $data['documentNumber'],
            QuoteDocumentsEnum::CAR_TAX_INVOICE => $embeddedTransaction->tax_invoice_no = $data['documentNumber'],
            QuoteDocumentsEnum::CAR_EP_TAX_INVOICE => $embeddedTransaction->tax_invoice_no = $data['documentNumber'],
            default => null,
        };

        $docNameSuffix = Str::after($oldDocument->doc_name, '_');

        /**
         * Keep insurer-document download naming aligned with existing behavior:
         * doc_name is always prefixed by certificate_number for all EP insurer document types.
         */
        $storedDocName = "{$embeddedTransaction->certificate_number}_{$docNameSuffix}";

        return [
            'embeddedTransaction' => $embeddedTransaction,
            'oldDocument' => $oldDocument,
            'documentType' => $documentType,
            'quoteObject' => $quoteObject,
            'storedDocName' => $storedDocName,
        ];
    }

    /**
     * Stores the manual-override document row and queues watermark processing and sending document when applicable.
     */
    private function persistManualOverrideEpDocument(array $data): void
    {
        $embeddedTransaction = $data['embeddedTransaction'];
        $documentType = $data['documentType'];
        $documentTypeCode = $documentType->code;

        $newDocument = $embeddedTransaction->documents()->create([
            'doc_name' => $data['storedDocName'],
            'original_name' => $data['originalName'],
            'doc_url' => $data['filePathAzure'],
            'doc_mime_type' => 'application/pdf',
            'document_type_code' => $documentTypeCode,
            'document_type_text' => $documentType->text,
            'doc_uuid' => $data['docUuid'],
            'created_by_id' => Auth::id(),
            'is_manual_override' => true,
            'override_remarks' => $data['remarks'],
            'document_type_id' => $documentType->id,
        ]);

        if ($documentTypeCode === QuoteDocumentsEnum::CAR_TAX_INVOICE_RAISE_BY_BUYER) {
            return;
        }

        $quoteObject = $data['quoteObject'];
        $watermarkJob = new WatermarkDocumentsJob($newDocument->id, $quoteObject->uuid, $documentType->id);
        $watermarkJob->afterCommit();

        $quoteId = (int) $quoteObject->id;
        $epId = (int) $data['epId'];
        $modelType = $data['modelType'];
        $epSentToCustomerDocTypes = QuoteDocumentsEnum::getEpSentToCustomerDocTypes();

        Bus::chain([
            $watermarkJob,
            static function () use ($documentTypeCode, $quoteId, $epId, $modelType, $epSentToCustomerDocTypes): void {
                if (! in_array($documentTypeCode, $epSentToCustomerDocTypes, true)) {
                    return;
                }

                $sendResult = EmbeddedProductRepository::sendDocument([
                    'epId' => $epId,
                    'modelType' => $modelType,
                    'quoteId' => $quoteId,
                ]);
                self::ensureQueueEmbeddedProductSendSucceeded(
                    $sendResult,
                    $quoteId,
                    $epId,
                    $modelType,
                    $documentTypeCode
                );
            },
        ])->delay(now()->addSeconds(10))->dispatch();
    }

    /**
     * Fails the queued chain job when auto-send returns a non-success payload so the job can retry and the failure is visible.
     *
     * @param  array<string, mixed>|null  $sendResult
     */
    private static function ensureQueueEmbeddedProductSendSucceeded(
        ?array $sendResult,
        int $quoteId,
        int $epId,
        string $modelType,
        string $documentTypeCode,
    ): void {
        if (is_array($sendResult) && ($sendResult['success'] ?? false) === true) {
            return;
        }

        $message = is_array($sendResult)
            ? (string) ($sendResult['message'] ?? 'Embedded product document send failed')
            : 'Embedded product document send failed';

        LoggerService::error('EP auto-send after manual document override failed', extra: [
            'quote_id' => $quoteId,
            'ep_id' => $epId,
            'model_type' => $modelType,
            'document_type_code' => $documentTypeCode,
            'message' => $message,
        ]);

        throw new EmbeddedProductDocumentSendFailedException($message);
    }
}
