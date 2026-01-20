<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\EmbeddedProductEnum;
use App\Enums\ProcessStatusCode;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeShortCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\GetStatusRetargetingEpReminderRequest;
use App\Http\Requests\Api\RetargetingEpReminderCallbackRequest;
use App\Http\Requests\EmbeddedProducDocumentRequest;
use App\Jobs\AddressReminderJob;
use App\Jobs\EP\SendEPJob;
use App\Models\CustomerAddress;
use App\Models\EmbeddedProduct;
use App\Repositories\EmbeddedTransactionRepository;
use App\Services\EmailStatusService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;

class EmbeddedProductController extends Controller
{
    use GenericQueriesAllLobs;

    public function sendDocument(EmbeddedProducDocumentRequest $request)
    {
        $data = $request->validated();
        $quoteId = intval($data['quoteId']);
        $modelType = ucfirst($data['modelType']);
        $epId = $data['epId'];
        SendEPJob::dispatch($quoteId, $modelType, $epId)->delay(now()->addSeconds(15));

        // check if policy is issued and address is not entered then call KEN API
        // Dispatch the job for sending an address reminder
        $ep = EmbeddedProduct::find($epId);
        if ($ep->short_code == EmbeddedProductEnum::COURIER && $modelType !== quoteTypeCode::Home) {
            $quote = $this->getQuoteObject($modelType, $quoteId);
            info('Checking if address is entered for lead in sendAddressReminderOnPolicyIssue : '.$quote->uuid);
            $address = CustomerAddress::where('quote_uuid', $quote->uuid)->first();
            if (empty($address?->type)) {
                AddressReminderJob::dispatch($quote, $modelType)->delay(now()->addSeconds(15));
            }
        }

        return apiResponse(null, Response::HTTP_OK, '');
    }

    public function getStatusRetargetingEpReminder(GetStatusRetargetingEpReminderRequest $request): JsonResponse
    {
        $quoteFields = ['id', 'code', 'quote_status_id', 'policy_booking_date', 'advisor_id'];
        $epTransactionFields = ['id', 'code', 'quote_type_id', 'quote_request_id', 'quote_request_type', 'is_selected', 'payment_status_id', 'product_id', 'policy_status'];
        $withAdvisorFields = 'advisor:id,email,name,mobile_no,landline_no,profile_photo_path';

        $model = $this->getModelObject(strtolower(QuoteTypeShortCode::getName($request->quoteTypeId)));
        $quote = $model ? $model::select(array_merge($quoteFields, ['customer_id', 'email', 'mobile_no', 'first_name', 'last_name']))
            ->with($withAdvisorFields)->find($request->quoteId) : null;

        if (!$quote) {
            return apiResponse(null, Response::HTTP_NOT_FOUND, 'Quote not found');
        }

        // if ($quote->quote_status_id != QuoteStatusEnum::PolicyBooked) {
        //     $data = $quote->only('id', 'code', 'quote_status_id', 'policy_booking_date');
        //     return apiResponse($data, Response::HTTP_BAD_REQUEST, 'Quote policy is not booked');
        // }

        $epTransaction = EmbeddedTransactionRepository::epTransactions($request->quoteTypeId, $quote->id)
            ->where('code', $request->embeddedTransactionCode)
            ->select($epTransactionFields)
            ->first();
        if (!$epTransaction) {
            return apiResponse(null, Response::HTTP_NOT_FOUND, 'Embedded transaction not found');
        }

        $advisorInfo = $quote->advisor ?? null;
        $reminderContent = [
            "customerEmail" => $quote->email,
            "customerName" => "{$quote->first_name} {$quote->last_name}",
            "customerMobileNumber" => $quote?->mobile_no,
            "advisorEmail" => $advisorInfo?->email,
            "advisorLandLine" => $advisorInfo?->landline_no,
            "advisorMobileNoWithoutSpaces" => removeSpaces($advisorInfo?->mobile_no ?? ''),
            "advisorMobileNumber" => $advisorInfo?->mobile_no,
            "advisorName" => $advisorInfo->name,
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
        return apiResponse($data, Response::HTTP_OK, 'Retargeting EP Reminder status retrieved');
    }

    public function retargetingEpReminderCallback(RetargetingEpReminderCallbackRequest $request): JsonResponse
    {
        $newEmailStatus = (object) [
            'quoteTypeId' => $request->quoteTypeId,
            'quoteId' => $request->quoteId,
            'customerEmail' => $request->customerIdentity,
            'templateId' => 123123123,
            'customerId' => $request->customerId
        ];

        $reminderNumberTitle = $request->reminderNumber == 1 ? 'First' : 'Second';
        $status = in_array($request->responseCode, [200, 201, 202]) ? ProcessStatusCode::SENT : ProcessStatusCode::FAILED;
        $emailStatusId = app(EmailStatusService::class)->addEmailStatus($newEmailStatus, $request->messageId, $request->subject, $status, "{$reminderNumberTitle} Reminder Email {$status}");

        return apiResponse(['email_status_id' => $emailStatusId], Response::HTTP_OK, 'Retargeting EP Reminder Email Sent');
    }
}
