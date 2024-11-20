<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmbeddedProducDocumentRequest;
use App\Jobs\EP\SendEPJob;
use Illuminate\Http\Response;
use App\Models\CustomerAddress;
use App\Jobs\AddressReminderJob;

class EmbeddedProductController extends Controller
{
    public function sendDocument(EmbeddedProducDocumentRequest $request)
    {
        $data = $request->validated();
        $quoteId = $data['quoteId'];
        $modelType = ucfirst($data['modelType']);
        $epId = $data['epId'];
        SendEPJob::dispatch($quoteId, $modelType, $epId)->delay(now()->addSeconds(15));

        // check if policy is issued and address is not entered then call KEN API
        // Dispatch the job for sending an address reminder
        if($modelType === QuoteTypes::CAR) {
            $quote = $this->getQuoteObject($modelType, $quoteId);
            info('Checking if address is entered for lead in sendAddressReminderOnPolicyIssue : ' . $quote->uuid);
            $address = CustomerAddress::where('quote_uuid', $quote->uuid)->first();
            if (!$address) {
                AddressReminderJob::dispatch($quote)->delay(now()->addSeconds(15));
            }
        }        

        return apiResponse(null, Response::HTTP_OK, '');
    }
}
