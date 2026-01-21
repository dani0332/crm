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
        $retargetingEpReminderBirdFlowUrl = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_CAR_EP_RETARGETING_REMINDER_FLOW_URL);

        if (empty($birdWorkflowUrl) || empty($retargetingEpReminderBirdFlowUrl)) {
            LoggerService::info("triggerBirdWorkflowForRetargetingEpReminder: Configuration URLs not found");
            return;
        }

        $getStatusRetargetingEpReminderUrl = route('get.status.retargeting-ep-reminder', ['quoteId' => $quote->id, 'quoteTypeId' => $quoteTypeId, 'embeddedTransactionCode' => $epTransaction->code]);

        $birdEmailData = [
            "quoteId" => $quote->id,
            "quoteTypeId" => $quoteTypeId,
            "refID" => $quote->code,
            "embeddedTransactionCode" => $epTransaction->code,
            "workflowType" => WorkflowTypeEnum::CAR_EP_RETARGETING_REMINDER,
            "getStatusRetargetingEpReminderUrl" => $getStatusRetargetingEpReminderUrl,
            "retargetingEpReminderFlowUrl" => $retargetingEpReminderBirdFlowUrl
        ];

        LoggerService::info("triggerBirdWorkflowForRetargetingEpReminder: ", extra: ['data' => $birdEmailData]);
        app(BirdService::class)->triggerWebHookRequest($birdWorkflowUrl, (object) $birdEmailData);
    }

    public function getRetargetingEpReminderData($quoteId, $quoteTypeId, $embeddedTransactionCode)
    {
        $quoteFields = ['id', 'code', 'quote_status_id', 'policy_booking_date', 'advisor_id'];
        $epTransactionFields = ['id', 'code', 'quote_type_id', 'quote_request_id', 'quote_request_type', 'is_selected', 'payment_status_id', 'product_id', 'policy_status'];

        $model = $this->getModelObject(strtolower(QuoteTypeShortCode::getName($quoteTypeId)));
        $quote = $model ? $model::select('customer_id', 'email', 'mobile_no', 'first_name', 'last_name', ...$quoteFields)
            ->with('advisor:id,email,name,mobile_no,landline_no,profile_photo_path')
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

        $advisorInfo = $quote->advisor ?? null;        
        if (empty($advisorInfo)) {
            return apiResponse(null, Response::HTTP_UNPROCESSABLE_ENTITY, 'Advisor is not assigned');
        }

        $reminderContent = [
            "customerEmail" => $quote->email,
            "customerName" => "{$quote->first_name} {$quote->last_name}",
            "customerMobileNumber" => formatMobileNo($quote?->mobile_no ?? ''),
            "advisorEmail" => $advisorInfo?->email,
            "advisorLandLine" => $advisorInfo?->landline_no,
            "advisorMobileNoWithoutSpaces" => removeSpaces($advisorInfo?->mobile_no ?? ''),
            "advisorMobileNumber" => formatMobileNo($advisorInfo?->mobile_no ?? ''),
            "advisorName" => $advisorInfo?->name,
            "advisorProfilePhotoPath" => $advisorInfo?->profile_photo_path,
            "DisplayName" => "InsuranceMarket.ae",
            "retargetingEpReminderCallbackEndpoint" => route('retargeting-ep-reminder-callback'),
            "customerId" => $quote->customer_id,
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
