<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SageApiService;
use App\Http\Requests\SageRequest;

class SageApi extends Controller
{
    protected $sageApiService;
    public function __construct(SageApiService $sageApi)
    {        
        $this->sageApiService = $sageApi;
    }

    public function index()
    {

        //$jsonResponse = $sageApi->postToARInvoiceBatches();

        $endPoint = "AR/ARInvoiceBatches";
               
        $payLoad = [
            "Invoices" => [
                [
                    "CurrencyCode" => "AED",
                    "CustomerNumber" => "C1111",
                    "DocumentDate" => "2023-04-26T00:00:00Z",
                    "DocumentNumber" => "334042",
                    "DocumentTotalBeforeTax" => 2000,
                    "DocumentTotalIncludingTax" => 2000,
                    "DueDate" => "2023-04-26T00:00:00Z",
                    "InvoiceDescription" => "DENBER SAMPLE haf2 ",
                    "InvoiceDetails" => [
                        [
                            "Description" => "DENBER SAMPLE DESC haf2",
                            "ExtendedAmountWithoutTIP" => 2000,
                            "ExtendedAmountWithTIP" => 2000,
                            "RevenueAccount" => "55020",
                            "TaxClass1" => 5
                        ]
                    ],
                    "InvoiceOptionalFields" => [
                        [
                            "OptionalField" => "CCCODE",
                            "Value" => "sample cc code"
                        ]
                    ],
                    "InvoicePaymentSchedules" => [
                        [
                            "DueDate" => "2023-04-26T00:00:00Z"
                        ]
                    ],
                    "PostingDate" => "2023-04-26T00:00:00Z",
                    "TaxAmount1" => 0,
                    "TaxClass1" => 5,
                    "TaxGroup" => "VAT"
                ],
                [
                    "CurrencyCode" => "AED",
                    "CustomerNumber" => "C1111",
                    "DocumentDate" => "2023-05-16T00:00:00Z",
                    "DocumentNumber" => "448008",
                    "DocumentTotalBeforeTax" => 110,
                    "DocumentTotalIncludingTax" => 115.5,
                    "DueDate" => "2023-05-26T00:00:00Z",
                    "InvoiceDescription" => "DENBER COMMISSION SAMPLE haf2",
                    "InvoiceDetails" => [
                        [
                            "Description" => "DC.ORI.MotorFleet.P/SZ/2031/21/00552 haf22",
                            "ExtendedAmountWithoutTIP" => 115.5,
                            "ExtendedAmountWithTIP" => 110,
                            "RevenueAccount" => "60010",
                            "TaxAmount1" => 5.5,
                            "TaxClass1" => 1
                        ]
                    ],
                    "InvoiceOptionalFields" => [
                        [
                            "OptionalField" => "CCCODE",
                            "Value" => "sample cc code"
                        ],
                        [
                            "OptionalField" => "STATE",
                            "Value" => "DXB"
                        ]
                    ],
                    "InvoicePaymentSchedules" => [
                        [
                            "DueDate" => "2023-05-26T00:00:00Z"
                        ]
                    ],
                    "PostingDate" => "2023-04-26T00:00:00Z",
                    "TaxAmount1" => 5.5,
                    "TaxClass1" => 1,
                    "TaxGroup" => "VAT"
                ]
            ]
        ];
        
       $jsonResponse = $this->sageApiService->postToSage300($endPoint,$payLoad);
        
        echo  $jsonResponse;
        
        die("dsdsdsd");


        
    }
    public function processSagePost(SageRequest $request)
    {
      //  return back()->with('message', 'Activity created successfully'); 
        echo "here"; die();
        
        $endPoint = "AR/ARInvoiceBatches";
        $payLoad  = "";
        $jsonResponse = $this->sageApiService->postToSage300($endPoint,$payLoad);
        
        echo $jsonResponse;
        //$data = $sageApi->postToARInvoiceBatches();

    }
}
