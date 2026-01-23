<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\EmbeddedProductEnum;
use App\Enums\ProcessStatusCode;
use App\Enums\QuoteTypeShortCode;
use App\Enums\WorkflowTypeEnum;
use App\Http\Requests\Api\RetargetingEpReminderCallbackRequest;
use App\Models\CarQuote;
use App\Models\EmbeddedTransaction;
use App\Repositories\EmbeddedTransactionRepository;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

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
        $quoteData = DB::table('car_quote_request as cqr')
            ->select(
                'cqr.id as quote.id',
                'cqr.uuid as quote.uuid',
                'cqr.code as quote.code',
                'cqr.quote_status_id as quote.quote_status_id',
                'cqr.policy_booking_date as quote.policy_booking_date',
                'cqr.customer_id as quote.customer_id',
                'cqr.advisor_id as quote.advisor_id',
                'et.code as embeddedTransaction.code',
                'et.is_selected as embeddedTransaction.is_selected',
                'et.payment_status_id as embeddedTransaction.payment_status_id',
                'et.product_id as embeddedTransaction.product_id',
                'et.policy_status as embeddedTransaction.policy_status',
                'cqr.email as customer.email',
                'cqr.mobile_no as customer.mobile_no',
                'cqr.first_name as customer.first_name',
                'cqr.last_name as customer.last_name',
                'adv.email as advisor.email',
                'adv.name as advisor.name',
                'adv.mobile_no as advisor.mobile_no',
                'adv.landline_no as advisor.landline_no',
                'adv.profile_photo_path as advisor.profile_photo_path',
                'cqr.plan_id as plan.id',
                'ip.code as plan.provider_code',
                )
                ->join('embedded_transactions as et', function ($join) use ($quoteTypeId) {
                    $join->on('et.quote_request_id', '=', 'cqr.id')
                        ->where('et.quote_request_type', '=', CarQuote::class)
                        ->where('et.quote_type_id', '=', $quoteTypeId);
                })
                ->join('users as adv', 'cqr.advisor_id', '=', 'adv.id')
                ->join('car_plan as cp', 'cqr.plan_id', '=', 'cp.id')
                ->join('insurance_provider as ip', 'cp.provider_id', '=', 'ip.id')
                ->where('cqr.id', $quoteId)
                ->first();

        if (empty($quoteData)) {
            return apiResponse(null, Response::HTTP_NOT_FOUND, 'Quote not found');
        }

        $quoteData = Arr::undot($quoteData);
        $quote = (object) $quoteData['quote'];
        $embeddedTransaction = (object) $quoteData['embeddedTransaction'];
        $customer = (object) $quoteData['customer'];
        $advisor = (object) $quoteData['advisor'];
        $plan = (object) $quoteData['plan'];

        if (empty($quote) || empty($embeddedTransaction) || empty($customer)) {
            return apiResponse(null, Response::HTTP_NOT_FOUND, 'Required data not found');
        }

        $buyNowLink = config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$quote->uuid.'/payment/?providerCode='.$plan?->provider_code.'&planId='.$plan?->id;

        $reminderContent = [
            "customerEmail" => $customer->email,
            "customerName" => "{$customer->first_name} {$customer->last_name}",
            "customerMobileNumber" => formatMobileNo($customer->mobile_no ?? ''),
            "advisorEmail" => $advisor?->email,
            "advisorLandLine" => $advisor?->landline_no,
            "advisorMobileNoWithoutSpaces" => removeSpaces($advisor?->mobile_no ?? ''),
            "advisorMobileNumber" => formatMobileNo($advisor?->mobile_no ?? ''),
            "advisorName" => $advisor?->name,
            "advisorProfilePhotoPath" => $advisor?->profile_photo_path,
            "DisplayName" => config('constants.IM_FROM_EMAIL','InsuranceMarket'),
            "retargetingEpReminderCallbackUrl" => route('retargeting-ep-reminder-callback'),
            "customerId" => $quote->customer_id,
            "buyNowLink" => $buyNowLink,
        ];

        $data = [
            'quote' => $quote,
            'embeddedTransaction' => $embeddedTransaction,
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
