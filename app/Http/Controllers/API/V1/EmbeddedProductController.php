<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\EmbeddedProductEnum;
use App\Enums\quoteTypeCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\GetEpWorkflowDataRequest;
use App\Http\Requests\EmbeddedProducDocumentRequest;
use App\Jobs\AddressReminderJob;
use App\Jobs\EP\SendEPJob;
use App\Models\CustomerAddress;
use App\Models\EmbeddedProduct;
use App\Services\EmbeddedTransactionService;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmbeddedProductController extends Controller
{
    use GenericQueriesAllLobs;

    public function __construct(
        private readonly EmbeddedTransactionService $embeddedTransactionService
    ) {}

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

    public function getEpWorkflowData(GetEpWorkflowDataRequest $request): JsonResponse
    {
        return $this->embeddedTransactionService->getEpRetargetingReminderData(
            (int) $request->quoteId,
            (int) $request->quoteTypeId,
            $request->embeddedTransactionCode,
        );
    }

    public function triggerEpRetargetingEmail(Request $request): JsonResponse
    {
        $data = null;
        if ($request->has('attributes')) {
            $data = isset($request['attributes']['data']) ? $request['attributes']['data'] : [];
            if (! isset($data['quoteId']) || ! isset($data['quoteTypeId']) || ! isset($data['embeddedTransactionCode'])) {
                LoggerService::info('Trigger EP Retargeting - Required attributes are missing', extra: ['request' => $request]);

                return response()->json(['message' => 'Required attributes are missing.'], Response::HTTP_BAD_REQUEST);
            }
        }
        $quoteId = (int) $data['quoteId'];
        $quoteTypeId = (int) $data['quoteTypeId'];
        $embeddedTransactionCode = $data['embeddedTransactionCode'];

        $isTriggerAllowed = $this->embeddedTransactionService->getEpRetargetingReminderData($quoteId, $quoteTypeId, $embeddedTransactionCode)?->getStatusCode() === Response::HTTP_OK;

        if (! $isTriggerAllowed) {
            return response()->json(['message' => 'Criteria not met for triggering the email.']);
        }

        // If criteria is matched, proceed to trigger the email

        LoggerService::info("Trigger EP Retargeting Reminder Email - Quote ID: {$quoteId}, Quote Type ID: {$quoteTypeId}, Embedded Transaction Code: {$embeddedTransactionCode}, Is Trigger Allowed: ".json_encode($isTriggerAllowed));

        $response = $this->embeddedTransactionService->triggerRetargetingEpReminderForBike($quoteId, $quoteTypeId, $embeddedTransactionCode);

        return response()->json($response);
    }
}
