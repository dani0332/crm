<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\EmbeddedProductEnum;
use App\Enums\WorkflowTypeEnum;
use App\Models\CarQuote;
use App\Models\EmbeddedTransaction;
use App\Repositories\EmbeddedTransactionRepository;
use App\Services\Logger\LoggerService;
use Illuminate\Http\Response;

class EmbeddedTransactionService extends BaseService
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        protected EmbeddedTransactionRepository $embeddedTransactionRepo,
        protected BirdService $birdService,
        protected EmailStatusService $emailStatusService
    ) {
        parent::__construct();
    }

    public function isRetargetingEpReminderEnabled(): bool
    {
        return (bool) $this->getAppStorageValueByKey(ApplicationStorageEnums::ENABLE_CAR_EP_RETARGETING_REMINDER);
    }
    public function getAppStorageValueByKey(string $appStorageKeyName): string|bool
    {
        return match ($appStorageKeyName) {
            ApplicationStorageEnums::BIRD_CAR_EP_RETARGETING_REMINDER_WORKFLOW_URL => getAppStorageValueByKey($appStorageKeyName, useCache: true, cacheTime: 120),
            ApplicationStorageEnums::ENABLE_CAR_EP_RETARGETING_REMINDER => getAppStorageValueByKey($appStorageKeyName),
            default => false,
        };
    }

    public function retargetEpReminder(CarQuote $quote, int $quoteTypeId)
    {
        $epTransactions = $this->embeddedTransactionRepo->getDraftEpTransactions($quote->id, $quoteTypeId, EmbeddedProductEnum::CAR_EP_RETARGETING_REMINDER_ALLOWED_EPS);
        if ($epTransactions->isEmpty()) {
            LoggerService::info('retargetEpReminder: Record not found', extra: ['quoteId' => $quote->id, 'quoteTypeId' => $quoteTypeId]);

            return [];
        }

        $response = [];
        foreach ($epTransactions as $epTransaction) {
            try {
                $result = $this->triggerBirdWorkflowRetargetEpReminder($quote, $quoteTypeId, $epTransaction);
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
                break;
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
        $birdWorkflowUrl = $this->getAppStorageValueByKey(ApplicationStorageEnums::BIRD_CAR_EP_RETARGETING_REMINDER_WORKFLOW_URL);
        if (empty($birdWorkflowUrl)) {
            return (object) ['status_code' => Response::HTTP_NOT_FOUND, 'message' => 'Bird EP Reminder Workflow URL not found'];
        }

        $getRetargetingEpReminderUrl = route('get.retargeting-ep-reminder', ['quoteId' => $quote->id, 'quoteTypeId' => $quoteTypeId, 'embeddedTransactionCode' => $epTransaction->code]);

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

    public function getRetargetingCarEpReminderData($carQuoteRequestId, $embeddedTransactionCode)
    {
        $embeddedTransaction = $this->embeddedTransactionRepo->getRetargetingCarEpReminderData($carQuoteRequestId, $embeddedTransactionCode);
        $quote = $embeddedTransaction?->quoteRequest ?? null;

        if (empty($embeddedTransaction) || empty($quote)) {
            return apiResponse(null, Response::HTTP_NOT_FOUND, 'Record not found');
        }

        $carMake = $quote->carMake?->text ?? null;
        $carModel = $quote->carModel?->text ?? null;
        $epShortCode = $embeddedTransaction->product?->embeddedProduct?->short_code ?? null;
        if (empty($carMake) || empty($carModel) || empty($epShortCode) || empty($quote->email) || empty($quote->customer_id)) {
            LoggerService::info('getRetargetingCarEpReminderData: Required data not found', extra: ['data' => $quote]);

            return apiResponse(null, Response::HTTP_NOT_FOUND, 'Required data not found');
        }

        $buyNowUrlQueryParams = [];
        if (! empty($quote->plan?->id ?? null)) {
            $buyNowUrlQueryParams['planId'] = $quote->plan?->id;
        }
        $providerCode = $quote->plan?->insuranceProvider?->code ?? null;
        if (! empty($providerCode)) {
            $buyNowUrlQueryParams['providerCode'] = $providerCode;
        }

        $buyNowUrl = config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$quote->uuid
            .'/payment/?'.http_build_query([
                ...$buyNowUrlQueryParams,
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
                // 'customerId' => $quote->customer_id,
                // 'displayName' => 'InsuranceMarket.ae',
            ],
        ];

        return apiResponse($data, Response::HTTP_OK, 'Retargeting EP Reminder data');
    }
}
