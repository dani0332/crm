<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\EmbeddedProductEnum;
use App\Enums\ProcessStatusCode;
use App\Enums\QuoteTypeShortCode;
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
            LoggerService::info("triggerBirdWorkflowForRetargetingEpReminder: Configuration URL not found");
            return;
        }

        $getRetargetingEpReminderUrl = route('get.retargeting-ep-reminder', ['quoteId' => $quote->id, 'quoteTypeId' => $quoteTypeId, 'embeddedTransactionCode' => $epTransaction->code]);

        $birdEmailData = [
            "quoteId" => $quote->id,
            "quoteTypeId" => $quoteTypeId,
            "refID" => $quote->code,
            "embeddedTransactionCode" => $epTransaction->code,
            "workflowType" => WorkflowTypeEnum::CAR_EP_RETARGETING_REMINDER,
            "getRetargetingEpReminderUrl" => $getRetargetingEpReminderUrl
        ];

        LoggerService::info("triggerBirdWorkflowForRetargetingEpReminder: ", extra: ['data' => $birdEmailData]);
        app(BirdService::class)->triggerWebHookRequest($birdWorkflowUrl, (object) $birdEmailData);
    }

    public function getRetargetingEpReminderData($quoteId, $quoteTypeId, $embeddedTransactionCode)
    {
        $quoteFields = ['id', 'code', 'quote_status_id', 'policy_booking_date'];
        $epTransactionFields = ['id', 'code', 'quote_type_id', 'quote_request_id', 'quote_request_type', 'is_selected', 'payment_status_id'];

        $model = $this->getModelObject(strtolower(QuoteTypeShortCode::getName($quoteTypeId)));
        $quote = $model ? $model::select('customer_id', 'advisor_id', 'insurance_provider_id', 'plan_id', 'uuid', 'email', 'mobile_no', 'first_name', 'last_name', ...$quoteFields)
            ->with('advisor:id,email,name,mobile_no,landline_no,profile_photo_path')
            ->with('insuranceProvider:id,code')
            ->find($quoteId) : null;

        if (empty($quote)) {
            return apiResponse(null, Response::HTTP_NOT_FOUND, 'Quote not found');
        }

        $epTransaction = EmbeddedTransactionRepository::epTransactions($quoteTypeId, $quote->id)
            ->where('code', $embeddedTransactionCode)
            ->select($epTransactionFields)
            ->first();
        if (empty($epTransaction)) {
            return apiResponse(null, Response::HTTP_NOT_FOUND, 'Embedded transaction not found');
        }

        $buyNowLink = config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$quote->uuid.'/payment/?providerCode='.$quote->insurance_provider?->code.'&planId='.$quote->plan_id;

        $reminderContent = [
            "customerEmail" => $quote->email,
            "customerName" => "{$quote->first_name} {$quote->last_name}",
            "customerMobileNumber" => formatMobileNo($quote?->mobile_no ?? ''),
            "advisorEmail" => $quote->advisor?->email,
            "advisorLandLine" => $quote->advisor?->landline_no,
            "advisorMobileNoWithoutSpaces" => removeSpaces($quote->advisor?->mobile_no ?? ''),
            "advisorMobileNumber" => formatMobileNo($quote->advisor?->mobile_no ?? ''),
            "advisorName" => $quote->advisor?->name,
            "advisorProfilePhotoPath" => $quote->advisor?->profile_photo_path,
            "DisplayName" => config('constants.IM_FROM_EMAIL','InsuranceMarket'),
            "retargetingEpReminderCallbackUrl" => route('retargeting-ep-reminder-callback'),
            "customerId" => $quote->customer_id,
            "buyNowLink" => $buyNowLink,
        ];

        $data = [
            'quote' => Arr::only($quote->toArray(), $quoteFields),
            'embeddedTransaction' => Arr::only($epTransaction, $epTransactionFields),
            'reminderContent' => $reminderContent,
        ];

        return apiResponse($data, Response::HTTP_OK, 'Retargeting EP Reminder data');
    }

    public function retargetingEpReminderCallback(RetargetingEpReminderCallbackRequest $request)
    {
        $templateId = getAppStorageValueByKey(ApplicationStorageEnums::CAR_EP_RETARGETING_REMINDER_EMAIL_TEMPLATE);

        $newEmailStatus = (object) [
            'quoteTypeId' => $request->quoteTypeId,
            'quoteId' => $request->quoteId,
            'customerEmail' => $request->customerIdentity,
            'templateId' => $templateId,
            'customerId' => $request->customerId
        ];

        $successResponseCodes = [Response::HTTP_OK, Response::HTTP_CREATED, Response::HTTP_ACCEPTED];
        $status = in_array($request->responseCode, $successResponseCodes) ? ProcessStatusCode::SENT : ProcessStatusCode::FAILED;

        $reminderNumberTitle = $request->reminderNumber == 1 ? 'First' : 'Second';
        $emailStatusId = app(EmailStatusService::class)->addEmailStatus($newEmailStatus, $request->messageId, $request->subject, $status, "{$reminderNumberTitle} Reminder Email {$status}");

        return apiResponse(['email_status_id' => $emailStatusId], Response::HTTP_OK, "{$reminderNumberTitle} Reminder Email {$status}");
    }
}
