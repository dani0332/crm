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

    public function processSagePostTest()
    {
        $leadStatus = 'policy booked';
        $request = new SageRequest();
        $request->insurerPremiumTaxInvoiceNumber = '993456e';
        $request->invoicePaymentStatus = 'pending';
        $request->premiumWithoutTax = 100;
        $request->discount = 0;
        
        $request->insurerInvoiceDate = "2023-03-22";
        $request->policyExpiryDate = "2025-03-22";        
        $request->premiumWithTax = 100;
        $request->vatOnCommission = 0;
        $request->commission = 0;
        $request->commissionIncludingVat = 0;



        $request->callExtra = true;
        $messageFromAP = $this->processRequest($request, $leadStatus);

        dd($messageFromAP);
        $request->callExtra = false;
        ////$message = $this->processRequest($request, $leadStatus); //will create prem and commision

        
        $request->invoicePaymentStatus = 'paid';
        $createPrepaymentReciept = [
            'BatchRecordType' => 'CA',
            'ReceiptsAdjustments' => [
                [
                    'BatchType' => 'CA',
                    'CustomerNumber' => 'IC008',
                    'BankReceiptAmount' => floatval($request->premiumWithoutTax),
                    'CheckReceiptNumber' => '123456',
                    'PaymentCode' => 'BT',
                    'ReceiptTransactionType' => 'Prepayment',
                    'AppliedReceiptsAdjustments' => [
                        [
                            'BatchType' => 'CA',
                            'CustomerNumber' => 'IC008',
                            'ReceiptTransactionType' => 'Prepayment',
                        ],
                    ],
                ],
            ],
        ];
        $message = $this->sageApiService->postToSage300('AR/ARReceiptAndAdjustmentBatches', $createPrepaymentReciept);
        $responseData = json_decode($message, true);
        $documentNumberForReciept = $responseData['ReceiptsAdjustments'][0]['DocumentNumber'];
        $batchNumberReciept = $responseData['ReceiptsAdjustments'][0]['BatchNumber'];
        
        
        echo $batchNumberReciept;
        $createPostBatchStatus = [
            'BatchStatus' => 'ReadyToPost',
        ];
        $message = $this->sageApiService->postToSage300('AR/ARInvoiceBatches('.$batchNumberReciept.')', $createPostBatchStatus);
        $responseData = json_decode($message, true);
        echo "<pre>"; print_r($responseData);
        $createPostBatchStatus = [
            'BatchStatus' => 'Posted',
        ];
        $message = $this->sageApiService->postToSage300('AR/ARInvoiceBatches('.$batchNumberReciept.')', $createPostBatchStatus);
        $responseData = json_decode($message, true);
        echo "<pre>"; print_r($responseData);

        dd($responseData);
        
        //session(['documentNumberForReciept' => $documentNumberForReciept]);

        
        //HERE I NEED TO CALL NEW ENDPOINTS TO POST ON SAGE DASHBOARD

        ////$message = $this->processRequest($request, $leadStatus);
        
        
        
        
        echo  $documentNumberForReciept; 
        dd($responseData);
        exit;

    }

    public function processSagePost(SageRequest $request)
    {
        $leadStatus = 'policy booked';
        if (! $request->premiumWithoutTax > 0) {
            return back()->with('error', 'This lead does not have premium,select another lead');
        }

        //session(['documentNumberForReciept' => '']);
        if (strtolower($request->invoicePaymentStatus) == 'paid' && $leadStatus == 'policy booked' &&
            session('documentNumberForReciept') == '') {

            $createPrepaymentReciept = [
                'BatchRecordType' => 'CA',
                'ReceiptsAdjustments' => [
                    [
                        'BatchType' => 'CA',
                        'CustomerNumber' => 'IC008',
                        'BankReceiptAmount' => floatval($request->premiumWithoutTax),
                        'CheckReceiptNumber' => '123456',
                        'PaymentCode' => 'BT',
                        'ReceiptTransactionType' => 'Prepayment',
                        'AppliedReceiptsAdjustments' => [
                            [
                                'BatchType' => 'CA',
                                'CustomerNumber' => 'IC008',
                                'ReceiptTransactionType' => 'Prepayment',
                            ],
                        ],
                    ],
                ],
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

        if (! ($request->discount > 0) && $leadStatus == 'policy booked' && strtolower($request->invoicePaymentStatus) != 'paid') {
            $request->callExtra = true;
            $messageFromAP = $this->processRequest($request, $leadStatus);

        }
        $request->callExtra = false;
        $message = $this->processRequest($request, $leadStatus);

        return $message;
        ////return back()->with('message', $message.$messageFromAP);
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
