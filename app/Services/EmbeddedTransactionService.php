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

    public function retargetEpReminder($quote, int $quoteTypeId): void
    {
        $epTransactions = $this->embeddedTransactionRepo->getDraftEpTransactions($quote->id, $quoteTypeId, [EmbeddedProductEnum::MDX, EmbeddedProductEnum::ECB]);
        foreach ($epTransactions as $epTransaction) {
            $this->triggerBirdWorkflowForRetargetingEpReminder($quote, $quoteTypeId, $epTransaction);
        }
    }

    /**
     * This function use to trigger bird workflow
     */
    private function triggerBirdWorkflowForRetargetingEpReminder($quote, int $quoteTypeId, EmbeddedTransaction $epTransaction)
    {
        if (! (bool) getAppStorageValueByKey(ApplicationStorageEnums::ENABLE_CAR_EP_RETARGETING_REMINDER)) {
            LoggerService::info('triggerBirdWorkflowForRetargetingEpReminder: Retargeting EP Reminder is not enabled');
            return;
        }

        $birdWorkflowUrl = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_CAR_EP_RETARGETING_REMINDER_WORKFLOW_URL);

        if (empty($birdWorkflowUrl)) {
            LoggerService::info('triggerBirdWorkflowForRetargetingEpReminder: Configuration URL not found');
            return;
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

        LoggerService::info('triggerBirdWorkflowForRetargetingEpReminder: ', extra: ['data' => $birdEmailData]);
        app(BirdService::class)->triggerWebHookRequest($birdWorkflowUrl, (object) $birdEmailData);
    }

    public function getRetargetingCarEpReminderData($carQuoteRequestId, $embeddedTransactionCode)
    {
        $quoteData = $this->embeddedTransactionRepo->getRetargetingCarEpReminderData($carQuoteRequestId, $embeddedTransactionCode);

        if (empty($quoteData)) {
            return apiResponse(null, Response::HTTP_NOT_FOUND, 'Quote not found');
        }

        $quoteData = (object) array_map(fn ($item) => (object) $item, Arr::undot((array) $quoteData));
        $quote = $quoteData->quote;
        $embeddedTransaction = $quoteData->embeddedTransaction;
        $customer = $quoteData->customer;
        $advisor = $quoteData->advisor;
        $plan = $quoteData->plan;
        $vehicle = $quoteData->vehicle;

        if($quote?->quote_status_id != QuoteStatusEnum::PolicyBooked) {
            LoggerService::info('getRetargetingCarEpReminderData: Quote is not booked', extra: ['quote_status_id' => $quote?->quote_status_id]);
            return apiResponse(null, Response::HTTP_BAD_REQUEST, 'Quote is not booked');
        }
        if($embeddedTransaction?->payment_status_id != PaymentStatusEnum::DRAFT) {
            LoggerService::info('getRetargetingCarEpReminderData: Embedded transaction payment status is not draft', extra: ['et_payment_status_id' => $embeddedTransaction?->payment_status_id]);
            return apiResponse(null, Response::HTTP_BAD_REQUEST, 'Embedded transaction payment status is not draft');
        }

        if (empty($quote?->uuid)
            || empty($embeddedTransaction?->code)
            || empty($embeddedTransaction?->ep_short_code)
            || empty($customer?->id) || empty($customer?->email)
            || empty($vehicle?->make) || empty($vehicle?->model)
        ) {
            LoggerService::info('getRetargetingCarEpReminderData: Required data not found', extra: ['data' => $quoteData]);
            return apiResponse(null, Response::HTTP_NOT_FOUND, 'Required data not found');
        }

        if (! in_array($embeddedTransaction->ep_short_code, [EmbeddedProductEnum::MDX, EmbeddedProductEnum::ECB])) {
            LoggerService::info('getRetargetingCarEpReminderData: Invalid EP short code', extra: ['data' => $quoteData]);
            return apiResponse(null, Response::HTTP_BAD_REQUEST, 'Invalid EP short code');
        }

        $templateKey = match($embeddedTransaction->ep_short_code) {
            EmbeddedProductEnum::MDX => ApplicationStorageEnums::CAR_EP_REMINDER_MDX_EMAIL_TEMPLATE,
            EmbeddedProductEnum::ECB => ApplicationStorageEnums::CAR_EP_REMINDER_ECB_EMAIL_TEMPLATE,
            default => null,
        };

        $templateId = getAppStorageValueByKey($templateKey);
        $birdCarEpReminderEmailWorkflowUrl = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_CAR_EP_REMINDER_EMAIL_WORKFLOW_URL);
        if (empty($templateKey) || empty($templateId) || empty($birdCarEpReminderEmailWorkflowUrl)) {
            LoggerService::info('getRetargetingCarEpReminderData: Template / Email Workflow URL not found', extra: ['templateKey' => $templateKey, 'templateId' => $templateId, 'birdReminderEmailWorkflowUrl' => $birdCarEpReminderEmailWorkflowUrl]);
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
            'quote' => $quote,
            'embeddedTransaction' => $embeddedTransaction,
            'emailWorkflowData' => [
                'templateId' => $templateId,
                'customerId' => $customer->id,
                'customerEmail' => $customer->email,
                'customerName' => trim(($customer?->first_name ?? '').' '.($customer?->last_name ?? '')),
                'advisorEmail' => $advisor?->email,
                'displayName' => config('constants.IM_FROM_EMAIL', 'InsuranceMarket'),
                'buyNowUrl' => $buyNowUrl,
                'birdCarEpReminderEmailWorkflowUrl' => $birdCarEpReminderEmailWorkflowUrl,
                'birdCarEpReminderEmailCallbackUrl' => route('retargeting-ep-reminder-callback'),
                "epShortCode" => $embeddedTransaction->ep_short_code,
                "vehicleMake" => $vehicle->make,
                "vehicleModel" => $vehicle->model,
                // 'advisorName' => $advisor?->name,
                // 'customerMobileNumber' => formatMobileNo($customer?->mobile_no ?? ''),
                // 'advisorLandLine' => $advisor?->landline_no,
                // 'advisorMobileNoWithoutSpaces' => removeSpaces($advisor?->mobile_no ?? ''),
                // 'advisorMobileNumber' => formatMobileNo($advisor?->mobile_no ?? ''),
                // 'advisorProfilePhotoPath' => $advisor?->profile_photo_path,
            ],
        ];

        return apiResponse($data, Response::HTTP_OK, 'Retargeting EP Reminder data');
    }

    public function retargetingCarEpReminderCallback(RetargetingEpReminderCallbackRequest $request)
    {
        $newEmailStatus = (object) [
            'quoteTypeId' => $request->quoteTypeId,
            'quoteId' => $request->quoteId,
            'customerEmail' => $request->customerEmail,
            'templateId' => $request->templateId,
            'customerId' => $request->customerId,
        ];

        $successResponseCodes = [Response::HTTP_OK, Response::HTTP_CREATED, Response::HTTP_ACCEPTED];
        $status = in_array($request->responseCode, $successResponseCodes) ? ProcessStatusCode::SENT : ProcessStatusCode::FAILED;

        $reminderNumberTitle = $request->reminderNumber == 1 ? 'First' : 'Second';
        $emailStatusId = app(EmailStatusService::class)->addEmailStatus($newEmailStatus, $request->messageId, $request->subject, $status, "{$reminderNumberTitle} Reminder Email {$status}");

        return apiResponse(['email_status_id' => $emailStatusId], Response::HTTP_OK, "{$reminderNumberTitle} Reminder Email {$status}");
    }
}
