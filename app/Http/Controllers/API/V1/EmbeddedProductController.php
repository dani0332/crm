<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\EmbeddedProductEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmbeddedProducDocumentRequest;
use App\Jobs\AddressReminderJob;
use App\Jobs\EP\SendEPJob;
use App\Models\CustomerAddress;
use App\Models\EmbeddedProduct;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\SukoonMedexEPFailureNotification;
use App\Services\Logger\LoggerService;
use Exception;

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

    public function testEmail(Request $request)
    {
        $message = 'SukoonMedexPurchaseFlowJob - Medex EP failure notification email';

        try{
            $quoteType = QuoteTypes::getNameShortCode(strtoupper($request->modelType));
            $quote = $this->getQuoteObject($quoteType?->value, $request->quoteId ?? null);

            if(!$quote)
                throw new Exception('Quote not found');
            
            Mail::send(new SukoonMedexEPFailureNotification($quote, $quoteType?->id(), $request->fromIM ?? false));
            LoggerService::info("{$message} sent successfully");

            return response()->json([
                'requestBody' => $request->all(),
                'message' => "{$message} sent successfully",
            ], Response::HTTP_OK);

        } catch (Exception $e) {

            LoggerService::info("{$message} sending failed: ",
                extra: [
                    'exception' => $e->getMessage()
                ]);

            return response()->json([
                'requestBody' => $request->all(), 
                'message' => "{$message} sending failed: ",
                'exception' => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
}
