<?php

namespace App\Factories;

use App\Models\QuoteRequestEntityMapping;
use App\Services\SageApiService;

class SagePayloadFactory
{
    public static function createPayload($request, $leadStatus)
    {
        $request->discount = floatval($request->discount);
        $request->insurerInvoiceDate = date('Y-m-d', strtotime($request->insurerInvoiceDate));
        $request->policyExpiryDate = date('Ymd', strtotime($request->policyExpiryDate));
        $request->premiumWithoutTax = floatval($request->premiumWithoutTax);
        $request->premiumWithTax = floatval($request->premiumWithTax);
        $request->vatOnCommission = floatval($request->vatOnCommission);
        $request->commission = floatval($request->commission);
        $request->commissionIncludingVat = floatval($request->commissionIncludingVat);

        // Logic to create different payloads based on request and leadStatus
        if (strtolower($request->invoicePaymentStatus) == 'paid' &&
                        $leadStatus == 'policy booked'
        ) {
            return self::createPaymontRecieptOneInvoice($request);
        } elseif ($request->discount > 0 &&
                    $leadStatus == 'policy booked' &&
                    strtolower($request->invoicePaymentStatus) != 'paid'
        ) {
            return self::createARInvoiceDis($request);
        } elseif ($request->callExtra) {
            return self::createAPInvoicePrem($request);
        } else {
            return self::createARInvoicePremAndComm($request);

        }
    }

    private static function createPaymontRecieptOneInvoice($request)
    {
        /*$sageApi = new SageApiService();
        $createPrepaymentReciept = [
            "BatchRecordType" => "CA",
            "ReceiptsAdjustments" => [
                [
                    "BatchType" => "CA",
                    "CustomerNumber" => "IC008",
                    "BankReceiptAmount" => $request->premiumWithTax,
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
        $message = $sageApi->postToSage300('AR/ARReceiptAndAdjustmentBatches', $createPrepaymentReciept);
        $responseData = json_decode($message, true);

        $documentNumber = $responseData['ReceiptsAdjustments'][0]['DocumentNumber'];
        //echo $documentNumber; exit;
        //echo "<pre>"; print_r($responseData); exit;
        $documentNumber = "PP000039";*/
        $payLoad = [
            'BatchRecordType' => 'CA',
            'ReceiptsAdjustments' => [
                [
                    'BatchType' => 'CA',
                    'CustomerNumber' => 'IC008',
                    'ReceiptTransactionType' => 'ApplyDocument',
                    'DocumentNumber' => strval(session('documentNumberForReciept')),
                    'AppliedReceiptsAdjustments' => [
                        [
                            'BatchType' => 'CA',
                            'CustomerNumber' => 'IC008',
                            'DocumentNumber' => $request->insurerPremiumTaxInvoiceNumber.'-PREM',
                            'ReceiptTransactionType' => 'ApplyDocument',
                        ],
                    ],
                ],
            ],
        ];
        //dd($payLoad);
        /*$payLoad = [
            'BatchRecordType' => 'CA',
            'ReceiptsAdjustments' => [
                [
                    'BatchType' => 'CA',
                    'CustomerNumber' => $request->customerId,
                    'ReceiptTransactionType' => 'ApplyDocument',
                    'DocumentNumber' => $request->insurerPremiumTaxInvoiceNumber,
                    'AppliedReceiptsAdjustments' => [
                        [
                            'BatchType' => 'CA',
                            'CustomerNumber' => $request->customerId,
                            'DocumentNumber' => $request->insurerPremiumTaxInvoiceNumber,
                            'ReceiptTransactionType' => 'ApplyDocument',
                        ],
                    ],
                ],
            ],
        ];*/
        session(['documentNumberForReciept' => '']);

        return [
            'endPoint' => 'AR/ARReceiptAndAdjustmentBatches',
            'payload' => $payLoad,
        ];
    }

    private static function createAPInvoicePrem($request)
    {

        $payLoad = [
            'Invoices' => [
                [
                    'VendorNumber' => 'IP002',
                    'DocumentNumber' => $request->insurerPremiumTaxInvoiceNumber,
                    'InvoiceDescription' => $request->invoiceDescription,
                    'DocumentDate' => $request->insurerInvoiceDate,
                    'CurrencyCode' => 'AED',
                    'DueDate' => '2025-11-24T00:00:00Z',
                    'TaxGroup' => 'VAT',
                    'TaxClass1' => 5,
                    'TaxAmount1' => 0.000,
                    'DocumentTotalBeforeTaxes' => $request->premiumWithoutTax,
                    'DocumentTotalIncludingTax' => $request->premiumWithTax,
                    'PostingDate' => '2023-05-04T00:00:00Z',
                    'InvoiceDetails' => [
                        [
                            'DistributionDescription' => $request->invoiceDescription,
                            'TaxClass1' => 1,
                            'GLAccount' => '55020',
                            'DistributedAmount' => $request->premiumWithoutTax,
                            'DistributedAmountBeforeTaxes' => $request->premiumWithTax,
                        ],
                    ],
                    'InvoicePaymentSchedules' => [
                        [
                            'DueDate' => '2025-11-24T00:00:00Z',
                        ],
                    ],
                    'InvoiceOptionalFields' => self::createOptionalFields($request),
                ],
            ],
        ];

        return [
            'endPoint' => 'AP/APInvoiceBatches',
            'payload' => $payLoad,
        ];

    }

    private static function createARInvoiceDis($request)
    {
        // Payload creation logic for CreditNote scenario
        $payLoad = [
            'Invoices' => [
                [
                    'CustomerNumber' => $request->customerId,
                    'DocumentNumber' => $request->insurerPremiumTaxInvoiceNumber.'-DIS',
                    'InvoiceDescription' => $request->invoiceDescription,
                    'DocumentDate' => '2023-05-16T00:00:00Z',
                    'DocumentType' => 'CreditNote',
                    'CurrencyCode' => 'AED',
                    'DueDate' => '2023-05-26T00:00:00Z',
                    'ApplytoDocument' => '',
                    'TaxGroup' => 'VAT',
                    'TaxClass1' => 5,
                    'DocumentTotalBeforeTax' => $request->discount,
                    'DocumentTotalIncludingTax' => $request->discount,
                    'PostingDate' => $request->bookingDate,
                    'InvoiceDetails' => [
                        [
                            'Description' => $request->invoiceDescription,
                            'TaxClass1' => 5,
                            'RevenueAccount' => '70010',
                            'ExtendedAmountWithTIP' => $request->discount,
                            'ExtendedAmountWithoutTIP' => $request->discount,
                        ],
                    ],
                    'InvoicePaymentSchedules' => [
                        [
                            'DueDate' => '2023-05-16T00:00:00Z',
                        ],
                    ],
                    'InvoiceOptionalFields' => self::createOptionalFields($request),
                ],
            ],
        ];

        return [
            'endPoint' => 'AR/ARInvoiceBatches',
            'payload' => $payLoad,
        ];
    }

    private static function createARInvoicePremAndComm($request)
    {
        // Payload creation logic for default scenario
        $payLoad = [
            'Invoices' => [
                [
                    'CustomerNumber' => $request->customerId,
                    'DocumentNumber' => $request->insurerPremiumTaxInvoiceNumber.'-PREM',
                    'InvoiceDescription' => $request->invoiceDescription,
                    'DocumentDate' => '2023-04-26T00:00:00Z',
                    'CurrencyCode' => 'AED',
                    'DueDate' => '2023-04-26T00:00:00Z',
                    'TaxGroup' => 'VAT',
                    'TaxClass1' => 5,
                    'TaxAmount1' => 0.000,
                    'DocumentTotalBeforeTax' => $request->premiumWithoutTax,
                    'DocumentTotalIncludingTax' => $request->premiumWithTax,
                    'PostingDate' => $request->bookingDate,
                    'InvoiceDetails' => [
                        [
                            'Description' => $request->invoiceDescription,
                            'TaxClass1' => 5,
                            'RevenueAccount' => '55020',
                            'ExtendedAmountWithTIP' => $request->premiumWithTax,
                            'ExtendedAmountWithoutTIP' => $request->premiumWithoutTax,
                        ],
                    ],
                    'InvoicePaymentSchedules' => [
                        [
                            'DueDate' => '2023-04-26T00:00:00Z',
                        ],
                    ],
                    'InvoiceOptionalFields' => self::createOptionalFields($request),
                ],
                [
                    'CustomerNumber' => $request->customerId,
                    'DocumentNumber' => $request->insurerPremiumTaxInvoiceNumber.'-COM',
                    'InvoiceDescription' => $request->invoiceDescription,
                    'DocumentDate' => $request->insurerInvoiceDate,
                    'CurrencyCode' => 'AED',
                    'DueDate' => '2023-05-26T00:00:00Z',
                    'TaxGroup' => 'VAT',
                    'TaxClass1' => 1,
                    'TaxAmount1' => $request->vatOnCommission,
                    'DocumentTotalBeforeTax' => $request->commission,
                    'DocumentTotalIncludingTax' => $request->commissionIncludingVat,
                    'PostingDate' => $request->bookingDate,
                    'InvoiceDetails' => [
                        [
                            'Description' => $request->invoiceDescription,
                            'TaxClass1' => 1,
                            'TaxAmount1' => 5.5,
                            'RevenueAccount' => '60010',
                            'ExtendedAmountWithTIP' => $request->commissionIncludingVat,
                            'ExtendedAmountWithoutTIP' => $request->commission,
                        ],
                    ],
                    'InvoicePaymentSchedules' => [
                        [
                            'DueDate' => '2023-05-26T00:00:00Z',
                        ],
                    ],
                    'InvoiceOptionalFields' => self::createOptionalFields($request),
                ],
            ],
        ];

        return [
            'endPoint' => 'AR/ARInvoiceBatches',
            'payload' => $payLoad,
        ];
    }

    public static function createCustomerPayload($customer)
    {
        $data = $customer->data;
        $mapping = QuoteRequestEntityMapping::where([['quote_type_id', $data['quoteTypeId']], ['quote_request_id', $data['id']]])->first();
        if ($mapping) {
            $payLoad = [
                'CustomerNumber' => 'C'.$customer->id,
                'CustomerName' => $customer->first_name.' '.$customer->last_name,
                'GroupCode' => 'PHC',
            ];
        } else {
            $payLoad = [
                'CustomerNumber' => 'P'.$customer->id,
                'CustomerName' => $customer->first_name.' '.$customer->last_name,
                'GroupCode' => 'PHI',
            ];
        }

        return [
            'endPoint' => 'AR/ARCustomers',
            'payload' => $payLoad,
            'customerNumber' => $payLoad['CustomerNumber'],
        ];
    }

    /*
    public static function createCustomerPayload($customer)
    {
        $appendGroup = 'G';
        $customerNumber = self::customizeCustomerId($customer->id, $appendGroup);
        //dd($customerNumber);
        $payLoad = [
            'CustomerNumber' => $customerNumber.'H',
            'CustomerName' => $customer->first_name.' '.$customer->last_name,
            'GroupCode' => 'PHI',
        ];

        return [
            'endPoint' => 'AR/ARCustomers',
            'payload' => $payLoad,
            'customerNumber' => $customerNumber,
        ];
    }
*/
    public static function createPrepaymentPayload($request)
    {
        $payLoad = [
            'BatchRecordType' => 'CA',
            'ReceiptsAdjustments' => [
                [
                    'BatchType' => 'CA',
                    'CustomerNumber' => $request->sage_customer_number,
                    'BankReceiptAmount' => floatval($request->collection_amount),
                    'CheckReceiptNumber' => '123456',
                    'PaymentCode' => 'BT',
                    'ReceiptTransactionType' => 'Prepayment',
                    'AppliedReceiptsAdjustments' => [
                        [
                            'BatchType' => 'CA',
                            'CustomerNumber' => $request->sage_customer_number,
                            'ReceiptTransactionType' => 'Prepayment',
                        ],
                    ],
                ],
            ],
        ];

        return [
            'endPoint' => 'AR/ARReceiptAndAdjustmentBatches',
            'payload' => $payLoad,
        ];
    }

    private static function customizeCustomerId($customerId, $appendGroup, $minLength = 12)
    {
        $minLength = max(1, $minLength);
        $paddingLength = $minLength - strlen($customerId);
        if ($paddingLength < 0) {
            return $customerId;
        } else {
            $paddedCustomerId = str_repeat('0', $paddingLength).$customerId;
            $paddedCustomerId[0] = $appendGroup;

            return $paddedCustomerId;
        }
    }

    private static function createOptionalFields($request)
    {
        $optionalArray = [
            [
                'OptionalField' => 'CCCODE',
                'Value' => 'sample cc code',
            ],
            [
                'OptionalField' => 'ENDORSEMENT',
                'Value' => 'sample endorsement number',
            ],
            [
                'OptionalField' => 'EXPIRY',
                'Value' => $request->policyExpiryDate,
            ],
            [
                'OptionalField' => 'INCEPTION',
                'Value' => '20230505',
            ],
            [
                'OptionalField' => 'INSURED',
                'Value' => 'sample insured',
            ],
            [
                'OptionalField' => 'MAINCLASS',
                'Value' => $request->mainClassInsurance,
            ],
            [
                'OptionalField' => 'MANAGER',
                'Value' => 'sample manager',
            ],
            [
                'OptionalField' => 'PDC',
                'Value' => '0',
            ],
            [
                'OptionalField' => 'POLICY',
                'Value' => $request->policyNumber,
            ],
            [
                'OptionalField' => 'POLICYHOLDER',
                'Value' => 'sample policy holder',
            ],
            [
                'OptionalField' => 'POLICYISSUER',
                'Value' => $request->policyIssuer,
            ],
            [
                'OptionalField' => 'PREMIUM',
                'Value' => strval($request->premiumWithTax),
            ],
            [
                'OptionalField' => 'PREMIUMVAT',
                'Value' => '0.000',
            ],
            [
                'OptionalField' => 'REQUESTTYPE',
                'Value' => $request->requestType,
            ],
            [
                'OptionalField' => 'SALESPERSON',
                'Value' => $request->advisorName,
            ],
            [
                'OptionalField' => 'SUBCLASS',
                'Value' => $request->subClass,
            ],
            [
                'OptionalField' => 'CNTYPE',
                'Value' => 'Normal',
            ],
            [
                'OptionalField' => 'COLLECTS',
                'Value' => '',
            ],
            [
                'OptionalField' => 'COMMRATE',
                'Value' => '',
            ],
            [
                'OptionalField' => 'STATE',
                'Value' => 'DXB',
            ],
        ];

        return $optionalArray;
    }
}
