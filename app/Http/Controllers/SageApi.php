<?php

namespace App\Http\Controllers;

use App\Factories\SagePayloadFactory;
use App\Http\Requests\SageRequest;
use App\Services\SageApiService;
use Inertia\Inertia; // Import Inertia class

class SageApi extends Controller
{
    protected $sageApiService;
    public function __construct(SageApiService $sageApi)
    {
        $this->sageApiService = $sageApi;
    }

    public function index()
    {
        
    }

    public function processSagePost(SageRequest $request)
    {
        $leadStatus = 'policy booked';
        if(!$request->premiumWithoutTax>0){
            return back()->with('error', 'This lead does not have premium,select another lead');
        }

        //session(['documentNumberForReciept' => '']);
        if (strtolower($request->invoicePaymentStatus) == 'paid' && $leadStatus == 'policy booked' && 
            session('documentNumberForReciept')=='' ) {
            
            $createPrepaymentReciept = [
                "BatchRecordType" => "CA",
                "ReceiptsAdjustments" => [
                    [
                        "BatchType" => "CA",
                        "CustomerNumber" => "IC008",
                        "BankReceiptAmount" => floatval($request->premiumWithoutTax),
                        "CheckReceiptNumber" => "123456",
                        "PaymentCode" => "BT",
                        "ReceiptTransactionType" => "Prepayment",
                        "AppliedReceiptsAdjustments" => [
                            [
                                "BatchType" => "CA",
                                "CustomerNumber" => "IC008",
                                "ReceiptTransactionType" => "Prepayment"
                            ]
                        ]
                    ]
                ]
            ];
            $message = $this->sageApiService->postToSage300('AR/ARReceiptAndAdjustmentBatches', $createPrepaymentReciept);
            $responseData = json_decode($message, true);
            //echo "<pre>"; print_r($responseData); exit;            
            
            $documentNumberForReciept = $responseData['ReceiptsAdjustments'][0]['DocumentNumber'];
            
            session(['documentNumberForReciept' => $documentNumberForReciept]);

            return back()->with('message', 'Please POST Batch No '.$responseData['BatchNumber'].' Reciept and related Invoice from SAGE dashboard and submit again.');
            
            //return back()->with('error', 'This lead does not have premium,select another lead');
            //return self::createPaymontRecieptOneInvoice($request);
        }



        
        $messageFromAP = '';

        if( !($request->discount > 0) && $leadStatus == 'policy booked' && strtolower($request->invoicePaymentStatus) != 'paid' ) {
            $request->callExtra = TRUE;
            $messageFromAP = $this->processRequest($request, $leadStatus);
                        
        }
        $request->callExtra = FALSE;
        $message = $this->processRequest($request, $leadStatus);
        return back()->with('message', $message.$messageFromAP);
    }

    private function processRequest($request, $leadStatus)
    {
        $payLoadOptions = SagePayloadFactory::createPayload($request, $leadStatus);
        $endPoint = $payLoadOptions['endPoint'];
        $payLoad = $payLoadOptions['payload'];
        $jsonResponse = $this->sageApiService->postToSage300($endPoint, $payLoad);
        // Process the JSON response and handle messages
        $message = $this->processJsonResponse($jsonResponse);
        $message .= ' SAGE Endpoint= '.$endPoint;
        return $message;

    }


    private function processJsonResponse($message)
    {
        // Process the JSON response and extract message
        // ...

        $responseData = json_decode($message, true);
        if (isset($responseData['error'])) {
            return $responseData['error']['message']['value'];
        } else {
            return 'Batch Number '.$responseData['BatchNumber'].' created successfully';
        }

        return $responseData;
    }
}
