<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\EmbeddedProductEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\ProcessStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\WorkflowTypeEnum;
use App\Http\Requests\Api\RetargetingEpReminderCallbackRequest;
use App\Models\CarQuote;
use App\Models\EmbeddedTransaction;
use App\Repositories\EmbeddedTransactionRepository;
use App\Services\Logger\LoggerService;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;

class EmbeddedTransactionService extends BaseService
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        protected EmbeddedTransactionRepository $embeddedTransactionRepo
    ) {
        parent::__construct();
    }

    public function isRetargetingEpReminderEnabled(): bool
    {
        return (bool) $this->getAppStorageValueByKey(ApplicationStorageEnums::ENABLE_CAR_EP_RETARGETING_REMINDER);
    }
    public function getAppStorageValueByKey(string $appStorageKeyName): string | bool
    {
        return match($appStorageKeyName) {
            ApplicationStorageEnums::BIRD_CAR_EP_RETARGETING_REMINDER_WORKFLOW_URL => getAppStorageValueByKey($appStorageKeyName, useCache: true, cacheTime: 120),
            ApplicationStorageEnums::BIRD_CAR_EP_REMINDER_EMAIL_WORKFLOW_URL => getAppStorageValueByKey($appStorageKeyName, useCache: true, cacheTime: 120),
            ApplicationStorageEnums::ENABLE_CAR_EP_RETARGETING_REMINDER => getAppStorageValueByKey($appStorageKeyName),
            default => false,
        };
    }

    public function retargetEpReminder(CarQuote $quote, int $quoteTypeId)
    {
        if (! $this->isRetargetingEpReminderEnabled()) {
            LoggerService::info("retargetEpReminder: Retargeting EP Reminder is not enabled");
            return false;
        }

        $epTransactions = $this->embeddedTransactionRepo->getDraftEpTransactions($quote->id, $quoteTypeId, EmbeddedProductEnum::CAR_EP_RETARGETING_REMINDER_ALLOWED_EPS);
        if($epTransactions->isEmpty()) {
            LoggerService::info("retargetEpReminder: No draft EP transactions found");
            return false;
        }

        $response = [];
        foreach ($epTransactions as $epTransaction) {
            $result = $this->triggerBirdWorkflowRetargetEpReminder($quote, $quoteTypeId, $epTransaction);
            $response[] = ['embeddedTransactionCode' => $epTransaction->code, 'status_code' => $result->status_code, 'message' => $result->message ?? ''];
        }

        LoggerService::info("retargetEpReminder: Retargeting EP Reminder Successfully Triggered", extra: ['response' => $response]);

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
            'embeddedTransactionCode' => $epTransaction->code,
            'workflowType' => WorkflowTypeEnum::CAR_EP_RETARGETING_REMINDER,
            'getRetargetingEpReminderUrl' => $getRetargetingEpReminderUrl,
        ];

        LoggerService::info('triggerBirdWorkflowRetargetEpReminder: ', extra: ['data' => $birdEmailData]);
        return app(BirdService::class)->triggerWebHookRequest($birdWorkflowUrl, (object) $birdEmailData);
    }

    public function getRetargetingCarEpReminderData($carQuoteRequestId, $embeddedTransactionCode)
    {
        $quoteData = $this->embeddedTransactionRepo->getRetargetingCarEpReminderData($carQuoteRequestId, $embeddedTransactionCode);

        if (empty($quoteData)) {
            return apiResponse(null, Response::HTTP_NOT_FOUND, 'Quote not found');
        }

        $quoteData = (object) array_map(fn ($item) => (object) $item, Arr::undot((array) $quoteData));
        $quote = $quoteData->quote ?? null;
        $embeddedTransaction = $quoteData->embeddedTransaction ?? null;
        $advisor = $quoteData->advisor ?? null;
        $vehicle = $quoteData->vehicle ?? null;
        $plan = $quoteData->plan ?? null;

        if($quote?->quote_status_id != QuoteStatusEnum::PolicyBooked) {
            LoggerService::info('getRetargetingCarEpReminderData: Quote is not booked', extra: ['quote_status_id' => $quote?->quote_status_id]);
            return apiResponse(null, Response::HTTP_BAD_REQUEST, 'Quote is not booked');
        }
        if($embeddedTransaction?->payment_status_id != PaymentStatusEnum::DRAFT) {
            LoggerService::info('getRetargetingCarEpReminderData: Embedded transaction payment status is not draft', extra: ['et_payment_status_id' => $embeddedTransaction?->payment_status_id]);
            return apiResponse(null, Response::HTTP_BAD_REQUEST, 'Embedded transaction payment status is not draft');
        }

        $customerFullName = trim(($quote->first_name ?? '').' '.($quote->last_name ?? ''));
        if (empty($quote->uuid) || empty($quoteData->embeddedProduct->short_code)
            || empty($quote->customer_id) || empty($quote->email)
            || empty($customerFullName)
            || empty($embeddedTransaction->code)
            || empty($vehicle->make) || empty($vehicle->model)
        ) {
            LoggerService::info('getRetargetingCarEpReminderData: Required data not found', extra: ['data' => $quoteData]);
            return apiResponse(null, Response::HTTP_NOT_FOUND, 'Required data not found');
        }

        $templateId = $this->embeddedTransactionRepo->getEpRetargetingReminderEmailTemplateId($quoteData->embeddedProduct->short_code);
        $birdCarEpReminderEmailWorkflowUrl = $this->getAppStorageValueByKey(ApplicationStorageEnums::BIRD_CAR_EP_REMINDER_EMAIL_WORKFLOW_URL);

        if (empty($templateId) || empty($birdCarEpReminderEmailWorkflowUrl)) {
            LoggerService::info('getRetargetingCarEpReminderData: Template / Email Workflow URL not found', extra: ['templateId' => $templateId, 'birdReminderEmailWorkflowUrl' => $birdCarEpReminderEmailWorkflowUrl]);
            return apiResponse(null, Response::HTTP_BAD_REQUEST, 'Template / Email Workflow URL not found');
        }

        $buyNowUrlQueryParams = [];
        if (! empty($plan?->id))
            $buyNowUrlQueryParams['planId'] = $plan->id;
        if (! empty($plan?->provider_code))
            $buyNowUrlQueryParams['providerCode'] = $plan->provider_code;

        $buyNowUrl = config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$quote->uuid
            .'/payment/?'.http_build_query([
                ...$buyNowUrlQueryParams,
                'selectEtCode' => $embeddedTransaction->code,
            ]);

        $data = [
            'quote' => Arr::only((array) $quote, ['id', 'uuid', 'quote_status_id', 'policy_booking_date']),
            'embeddedTransaction' => $embeddedTransaction,
            'emailWorkflowData' => [
                'templateId' => $templateId,
                'customerId' => $quote->customer_id,
                'customerEmail' => $quote->email,
                'customerName' => $customerFullName,
                'advisorEmail' => $advisor->email ?? null,
                'displayName' => config('constants.IM_FROM_EMAIL'),
                'buyNowUrl' => $buyNowUrl,
                'birdCarEpReminderEmailWorkflowUrl' => $birdCarEpReminderEmailWorkflowUrl,
                'retargetingEpReminderCallbackUrl' => route('retargeting-ep-reminder-callback'),
                "epShortCode" => $quoteData->embeddedProduct->short_code,
                "vehicleMake" => $vehicle->make,
                "vehicleModel" => $vehicle->model
            ],
        ];

        return apiResponse($data, Response::HTTP_OK, 'Retargeting EP Reminder data');
    }

    public function retargetingCarEpReminderCallback(RetargetingEpReminderCallbackRequest $request)
    {
        $response = app(EmailStatusService::class)->addBirdEmailStatus($request);
        if ($response->status) {
            return apiResponse(null, Response::HTTP_OK, $response->message);
        }

        return apiResponse(null, Response::HTTP_NOT_FOUND, $response->message);
    }
}
