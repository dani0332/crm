<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\EmbeddedProductEnum;
use App\Enums\quoteTypeCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\GetRetargetingEpReminderRequest;
use App\Http\Requests\Api\RetargetingEpReminderCallbackRequest;
use App\Http\Requests\EmbeddedProducDocumentRequest;
use App\Jobs\AddressReminderJob;
use App\Jobs\EP\SendEPJob;
use App\Models\CustomerAddress;
use App\Models\EmbeddedProduct;
use App\Services\EmbeddedTransactionService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

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

    public function getRetargetingEpReminderData(GetRetargetingEpReminderRequest $request): JsonResponse
    {
        return app(EmbeddedTransactionService::class)
            ->getRetargetingEpReminderData($request->quoteId, $request->quoteTypeId, $request->embeddedTransactionCode);
    }

    public function retargetingEpReminderCallback(RetargetingEpReminderCallbackRequest $request): JsonResponse
    {
        return app(EmbeddedTransactionService::class)->retargetingEpReminderCallback($request);
    }
}
