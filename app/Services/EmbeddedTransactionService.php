<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\EmbeddedProductEnum;
use App\Enums\ProcessStatusCode;
use App\Enums\WorkflowTypeEnum;
use App\Http\Requests\Api\RetargetingEpReminderCallbackRequest;
use App\Models\EmbeddedTransaction;
use App\Repositories\EmbeddedTransactionRepository;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;

class EmbeddedTransactionService extends BaseService
{
    use GenericQueriesAllLobs;

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
        $birdWorkflowUrl = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_CAR_EP_RETARGETING_REMINDER_WORKFLOW_URL);

        if (empty($birdWorkflowUrl)) {
            LoggerService::info('triggerBirdWorkflowForRetargetingEpReminder: Configuration URL not found');

            return;
        }

        $getRetargetingEpReminderUrl = route('get.retargeting-ep-reminder', ['quoteId' => $quote->id, 'quoteTypeId' => $quoteTypeId, 'embeddedTransactionCode' => $epTransaction->code]);

        $birdEmailData = [
            'quoteId' => $quote->id,
            'quoteTypeId' => $quoteTypeId,
            'refID' => $quote->code,
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
        $customer = $quoteData->customer;
        $advisor = $quoteData->advisor;
        $plan = $quoteData->plan;

        if (empty($quoteData?->quote?->uuid)
            || empty($quoteData?->embeddedTransaction?->code)
            || empty($customer?->id) || empty($customer?->email)
            || empty($plan?->provider_code) || empty($plan?->id)
        ) {
            return apiResponse(null, Response::HTTP_NOT_FOUND, 'Required data not found');
        }

        $buyNowLink = config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$quoteData->quote->uuid
            .'/payment/?providerCode='.$plan->provider_code
            .'&planId='.$plan->id;

        $data = [
            'quote' => $quoteData->quote,
            'embeddedTransaction' => $quoteData->embeddedTransaction,
            'reminderContent' => [
                'customerId' => $customer->id,
                'customerEmail' => $customer->email,
                'customerName' => trim(($customer?->first_name ?? '').' '.($customer?->last_name ?? '')),
                'customerMobileNumber' => formatMobileNo($customer?->mobile_no ?? ''),
                'advisorEmail' => $advisor?->email,
                'advisorLandLine' => $advisor?->landline_no,
                'advisorMobileNoWithoutSpaces' => removeSpaces($advisor?->mobile_no ?? ''),
                'advisorMobileNumber' => formatMobileNo($advisor?->mobile_no ?? ''),
                'advisorName' => $advisor?->name,
                'advisorProfilePhotoPath' => $advisor?->profile_photo_path,
                'DisplayName' => config('constants.IM_FROM_EMAIL', 'InsuranceMarket'),
                'buyNowLink' => $buyNowLink,
                'retargetingEpReminderCallbackUrl' => route('retargeting-ep-reminder-callback'),
            ],
        ];

        return apiResponse($data, Response::HTTP_OK, 'Retargeting EP Reminder data');
    }

    public function retargetingCarEpReminderCallback(RetargetingEpReminderCallbackRequest $request)
    {
        $templateId = getAppStorageValueByKey(ApplicationStorageEnums::CAR_EP_RETARGETING_REMINDER_EMAIL_TEMPLATE);

        $newEmailStatus = (object) [
            'quoteTypeId' => $request->quoteTypeId,
            'quoteId' => $request->quoteId,
            'customerEmail' => $request->customerIdentity,
            'templateId' => $templateId,
            'customerId' => $request->customerId,
        ];

        $successResponseCodes = [Response::HTTP_OK, Response::HTTP_CREATED, Response::HTTP_ACCEPTED];
        $status = in_array($request->responseCode, $successResponseCodes) ? ProcessStatusCode::SENT : ProcessStatusCode::FAILED;

        $reminderNumberTitle = $request->reminderNumber == 1 ? 'First' : 'Second';
        $emailStatusId = app(EmailStatusService::class)->addEmailStatus($newEmailStatus, $request->messageId, $request->subject, $status, "{$reminderNumberTitle} Reminder Email {$status}");

        return apiResponse(['email_status_id' => $emailStatusId], Response::HTTP_OK, "{$reminderNumberTitle} Reminder Email {$status}");
    }
}
