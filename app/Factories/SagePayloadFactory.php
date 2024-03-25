<?php

namespace App\Factories;

use App\Enums\quoteStatusCode;
use App\Enums\SageEnum;
use App\Models\Lookup;
use App\Models\QuoteRequestEntityMapping;
use App\Models\User;

class SagePayloadFactory
{
    public static function createPayload($request, $leadStatus)
    {
        $request->discount = floatval($request->discount);
        $request->insurerInvoiceDate = date('Y-m-d', strtotime($request->insurerInvoiceDate));
        $request->paymentDueDate = date('Y-m-d', strtotime($request->paymentDueDate));
        $request->policyExpiryDate = date('Ymd', strtotime($request->policyExpiryDate));
        $request->premiumWithoutTax = floatval($request->premiumWithoutTax);
        $request->premiumWithTax = floatval($request->premiumWithTax);
        $request->vatOnCommission = floatval($request->vatOnCommission);
        $request->commission = floatval($request->commission);
        $request->commissionIncludingVat = floatval($request->commissionIncludingVat);

        // Logic to create different payloads based on request and leadStatus

        if (
            strtolower($request->invoicePaymentStatus) == 'paid' &&
            $leadStatus == quoteStatusCode::PolicyBooked
        ) {
            return self::createPaymontRecieptOneInvoice($request);
        } elseif (
            $request->discount > 0 &&
            $leadStatus == quoteStatusCode::PolicyBooked &&
            strtolower($request->invoicePaymentStatus) != 'paid'
        ) {
            return self::createARInvoiceDis($request);
        } elseif ($request->callExtra) {
            return self::createAPInvoicePrem($request);
        } else {
            return self::createARInvoicePremAndComm($request);
        }
    }

    public static function createPaymontRecieptOneInvoice($request)
    {

        $payLoad = [
            'BatchRecordType' => 'CA',
            'ReceiptsAdjustments' => [
                [
                    'BatchType' => 'CA',
                    'CustomerNumber' => $request->customerId,
                    'ReceiptTransactionType' => 'ApplyDocument',
                    'DocumentType' => 'Prepayment',
                    'DocumentNumber' => $request->sage_reciept_id,
                    'AppliedReceiptsAdjustments' => [
                        [
                            'BatchType' => 'CA',
                            'CustomerNumber' => $request->customerId,
                            'DocumentNumber' => (string) $request->insurerPremiumNumber,
                            'ReceiptTransactionType' => 'ApplyDocument',
                            'CustomerReceiptAmount' => $request->premiumWithTax,
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

    public static function createAPInvoicePrem($request, $type = SageEnum::SCT_STRAIGHT, $revCorrDetails = [])
    {
        $payLoad = [
            'Invoices' => [
                [
                    'VendorNumber' => 'IP002', // use vender api to create vender in sage
                    'DocumentNumber' => $request->insurerPremiumNumber,
                    'InvoiceDescription' => $request->invoiceDescription,
                    'DocumentDate' => $request->insurerInvoiceDate,
                    'CurrencyCode' => 'AED', // alway will be AED discussed with denber
                    'DueDate' => $request->paymentDueDate,
                    'TaxGroup' => 'VAT', // alway will be VAT discussed with denber
                    'TaxClass1' => 5,
                    'TaxAmount1' => 0.000,
                    'DocumentTotalBeforeTaxes' => $request->premiumWithoutTax,
                    'DocumentTotalIncludingTax' => $request->premiumWithTax,
                    'PostingDate' => $request->bookingDate,
                    'InvoiceDetails' => [
                        [
                            'DistributionDescription' => $request->invoiceDescription,
                            'TaxClass1' => 5,
                            'GLAccount' => '55020',
                            'DistributedAmount' => $request->premiumWithoutTax,
                            'DistributedAmountBeforeTaxes' => $request->premiumWithTax,
                        ],
                    ],
                    'InvoicePaymentSchedules' => [
                        [
                            'DueDate' => $request->paymentDueDate,
                        ],
                    ],
                    'InvoiceOptionalFields' => self::createOptionalFields($request),
                ],
            ],
        ];

        $sageRequestType = SageEnum::SRT_CREATE_AP_PREM_INV;
        $entryType = SageEnum::SCT_STRAIGHT;

        // Payload uppdate logic for reversal and correction scenario
        if (in_array($type, [SageEnum::SCT_REVERSAL, SageEnum::SCT_CORRECTION]) && !empty($revCorrDetails)) {
            $entryType = $type;

            if ($type == SageEnum::SCT_REVERSAL) {
                $payLoad['Invoices'][0]['DocumentNumber'] = $revCorrDetails->Invoices[0]->DocumentNumber.'-REV'; 
                $payLoad['Invoices'][0]['InvoiceDescription'] = $revCorrDetails->Invoices[0]->InvoiceDescription.' - REVERSAL'; 
                $payLoad['Invoices'][0]['DocumentType'] = 'CreditNote'; 
                $payLoad['Invoices'][0]['ApplytoDocument'] = $revCorrDetails->Invoices[0]->DocumentNumber; 
                $sageRequestType = SageEnum::SRT_CREATE_AP_PREM_REV_INV;
            }

            if ($type == SageEnum::SCT_CORRECTION) {
                $payLoad['Invoices'][0]['DocumentNumber'] = $revCorrDetails->Invoices[0]->DocumentNumber.'-NEW'; 
                $payLoad['Invoices'][0]['InvoiceDescription'] = $revCorrDetails->Invoices[0]->InvoiceDescription.' - NEW'; 
                $payLoad['Invoices'][0]['DocumentType'] = 'DebitNote'; 
                $payLoad['Invoices'][0]['ApplytoDocument'] = $revCorrDetails->Invoices[0]->DocumentNumber;  //
                $sageRequestType = SageEnum::SRT_CREATE_AP_PREM_CORR_INV;
            }

        }

        return [
            'endPoint' => 'AP/APInvoiceBatches',
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType,
            'entry_type' => $entryType
        ];
    }

    public static function createARInvoiceDis($request, $type = SageEnum::SCT_STRAIGHT, $revCorrDetails = [])
    {
        // Payload creation logic for CreditNote scenario
        $payLoad = [
            'Invoices' => [
                [
                    'CustomerNumber' => $request->customerId,
                    'DocumentNumber' => $request->insurerPremiumNumber.'-DIS',
                    'InvoiceDescription' => $request->invoiceDescription,
                    'DocumentDate' => $request->insurerInvoiceDate,
                    'DocumentType' => 'CreditNote',
                    'CurrencyCode' => 'AED',
                    'DueDate' => $request->paymentDueDate,
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
                            'DueDate' => $request->paymentDueDate,
                        ],
                    ],
                    'InvoiceOptionalFields' => self::createOptionalFields($request),
                ],
            ],
        ];

        $sageRequestType = SageEnum::SRT_CREATE_AR_DISC_INV;
        $entryType = SageEnum::SCT_STRAIGHT;

        // Payload uppdate logic for reversal and correction scenario
        if (in_array($type, [SageEnum::SCT_REVERSAL, SageEnum::SCT_CORRECTION]) && !empty($revCorrDetails)) {
            $entryType = $type;

            if ($type == SageEnum::SCT_REVERSAL) {
                $payLoad['Invoices'][0]['DocumentNumber'] = $revCorrDetails->Invoices[0]->DocumentNumber.'-REV'; 
                $payLoad['Invoices'][0]['InvoiceDescription'] = $revCorrDetails->Invoices[0]->InvoiceDescription.' - REVERSAL'; 
                $payLoad['Invoices'][0]['DocumentType'] = 'DebitNote'; 
                $payLoad['Invoices'][0]['ApplytoDocument'] = $revCorrDetails->Invoices[0]->DocumentNumber; 
                $payLoad['Invoices'][0]['TaxAmount1'] = 0.000; 
                $payLoad['Invoices'][0]['InvoiceDetails'][0]['RevenueAccount'] = '55020'; 
                $sageRequestType = SageEnum::SRT_CREATE_AR_DISC_REV_INV;
            }

            if ($type == SageEnum::SCT_CORRECTION) {
                $payLoad['Invoices'][0]['DocumentNumber'] = $revCorrDetails->Invoices[0]->DocumentNumber.'-NEW'; 
                $payLoad['Invoices'][0]['InvoiceDescription'] = $revCorrDetails->Invoices[0]->InvoiceDescription.' - NEW'; 
                $payLoad['Invoices'][0]['DocumentType'] = 'CreditNote'; 
                $payLoad['Invoices'][0]['ApplytoDocument'] = $revCorrDetails->Invoices[0]->DocumentNumber;
                $payLoad['Invoices'][0]['TaxAmount1'] = 0.000;
                $payLoad['Invoices'][0]['InvoiceDetails'][0]['RevenueAccount'] = '55020'; 
                $sageRequestType = SageEnum::SRT_CREATE_AR_DISC_CORR_INV;
            }

        }

        return [
            'endPoint' => 'AR/ARInvoiceBatches',
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType,
            'entry_type' => $entryType
        ];
    }

    public static function createARInvoicePremAndComm($request, $type = SageEnum::SCT_STRAIGHT, $revCorrDetails = [])
    {
        // Payload creation logic for default scenario
        $taxClass = 1;
        if ($request->commissionIncludingVat > 0) {
            $taxClass = 1;
        } else {
            $taxClass = 2;
        }
        $payLoad = [
            'Invoices' => [
                [
                    'CustomerNumber' => $request->customerId,
                    'DocumentNumber' => $request->insurerPremiumNumber,
                    'InvoiceDescription' => $request->invoiceDescription.'-PREM',
                    'DocumentDate' => $request->insurerInvoiceDate,
                    'CurrencyCode' => 'AED',
                    'DueDate' => $request->paymentDueDate,
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
                            'DueDate' => $request->paymentDueDate,
                        ],
                    ],
                    'InvoiceOptionalFields' => self::createOptionalFields($request),
                ],
                [
                    'CustomerNumber' => $request->customerId,
                    'DocumentNumber' => $request->insurerCommissionNumber,
                    'InvoiceDescription' => $request->invoiceDescription.'-COM',
                    'DocumentDate' => $request->insurerInvoiceDate,
                    'CurrencyCode' => 'AED',
                    'DueDate' => $request->paymentDueDate,
                    'TaxGroup' => 'VAT',
                    'TaxClass1' => $taxClass,
                    'TaxAmount1' => $request->vatOnCommission,
                    'DocumentTotalBeforeTax' => $request->commission,
                    'DocumentTotalIncludingTax' => $request->commissionIncludingVat,
                    'PostingDate' => $request->bookingDate,
                    'InvoiceDetails' => [
                        [
                            'Description' => $request->invoiceDescription,
                            'TaxClass1' => $taxClass,
                            'TaxAmount1' => $request->vatOnCommission,
                            'RevenueAccount' => '60010',
                            'ExtendedAmountWithTIP' => $request->commissionIncludingVat,
                            'ExtendedAmountWithoutTIP' => $request->commission,
                        ],
                    ],
                    'InvoicePaymentSchedules' => [
                        [
                            'DueDate' => $request->paymentDueDate,
                        ],
                    ],
                    'InvoiceOptionalFields' => self::createOptionalFields($request),
                ],
            ],
        ];

        $sageRequestType = SageEnum::SRT_CREATE_AR_PREM_COMM_INV;
        $entryType = SageEnum::SCT_STRAIGHT;

        // Payload uppdate logic for reversal and correction scenario
        if (in_array($type, [SageEnum::SCT_REVERSAL, SageEnum::SCT_CORRECTION]) && !empty($revCorrDetails)) {
            $entryType = $type;
            $payLoad = json_decode($revCorrDetails);

            $applyToDocumentPrem = $payLoad->Invoices[0]->DocumentNumber;
            $applyToDocumentComm = $payLoad->Invoices[1]->DocumentNumber;
            unset($payLoad->BatchStatus);

            if ($type == SageEnum::SCT_REVERSAL) {
                
                $payLoad->Invoices[0]->DocumentNumber = $payLoad->Invoices[0]->DocumentNumber.'-REV'; 
                $payLoad->Invoices[0]->InvoiceDescription = $payLoad->Invoices[0]->InvoiceDescription.' - REVERSAL'; 
                $payLoad->Invoices[0]->DocumentType = 'CreditNote'; 
                $payLoad->Invoices[0]->ApplytoDocument = $applyToDocumentPrem; 
                
                $payLoad->Invoices[1]->DocumentNumber = $payLoad->Invoices[1]->DocumentNumber.'-REV'; 
                $payLoad->Invoices[1]->InvoiceDescription = $payLoad->Invoices[1]->InvoiceDescription.' - REVERSAL'; 
                $payLoad->Invoices[1]->DocumentType = 'CreditNote'; 
                $payLoad->Invoices[1]->ApplytoDocument = $applyToDocumentComm; 
                $sageRequestType = SageEnum::SRT_CREATE_AR_PREM_COMM_REV_INV;
            }

            if ($type == SageEnum::SCT_CORRECTION) {

                $payLoad->Invoices[0]->DocumentNumber = $payLoad->Invoices[0]->DocumentNumber.'-NEW'; 
                $payLoad->Invoices[0]->InvoiceDescription = $payLoad->Invoices[0]->InvoiceDescription.' - NEW'; 
                // $payLoad->Invoices[0]->DocumentDate = $request->insurerInvoiceDate; 
                // $payLoad->Invoices[0]->DueDate = $request->paymentDueDate; 
                $payLoad->Invoices[0]->DocumentTotalBeforeTax = $request->premiumWithoutTax;
                $payLoad->Invoices[0]->DocumentTotalIncludingTax = $request->premiumWithTax;
                // $payLoad->Invoices[0]->PostingDate = $request->bookingDate;
                $payLoad->Invoices[0]->DocumentType = 'DebitNote'; 
                $payLoad->Invoices[0]->ApplytoDocument = $applyToDocumentPrem;

                $payLoad->Invoices[0]->InvoiceDetails[0]->Description = $payLoad->Invoices[0]->InvoiceDescription;
                $payLoad->Invoices[0]->InvoiceDetails[0]->ExtendedAmountWithTIP = $request->premiumWithTax;
                $payLoad->Invoices[0]->InvoiceDetails[0]->ExtendedAmountWithoutTIP = $request->premiumWithoutTax;
                
                // $payLoad->Invoices[0]->InvoicePaymentSchedules[0]->DueDate = $request->paymentDueDate;
                $payLoad->Invoices[0]->InvoiceOptionalFields = self::createOptionalFields($request);

                // Commision Invoice Correction
                $payLoad->Invoices[1]->DocumentNumber = $payLoad->Invoices[1]->DocumentNumber.'-NEW'; 
                $payLoad->Invoices[1]->InvoiceDescription = $payLoad->Invoices[1]->InvoiceDescription.' - NEW'; 
                // $payLoad->Invoices[1]->DocumentDate = $request->insurerInvoiceDate; 
                // $payLoad->Invoices[1]->DueDate = $request->paymentDueDate; 
                $payLoad->Invoices[1]->DocumentTotalBeforeTax = $request->commission;
                $payLoad->Invoices[1]->DocumentTotalIncludingTax = $request->commissionIncludingVat;
                // $payLoad->Invoices[1]->PostingDate = $request->bookingDate;
                $payLoad->Invoices[1]->DocumentType = 'DebitNote'; 
                $payLoad->Invoices[1]->ApplytoDocument = $applyToDocumentComm;

                $payLoad->Invoices[1]->InvoiceDetails[0]->Description = $payLoad->Invoices[1]->InvoiceDescription;
                $payLoad->Invoices[1]->InvoiceDetails[0]->ExtendedAmountWithTIP = $request->commissionIncludingVat;
                $payLoad->Invoices[1]->InvoiceDetails[0]->ExtendedAmountWithoutTIP = $request->commission;
                
                // $payLoad->Invoices[1]->InvoicePaymentSchedules[0]->DueDate = $request->paymentDueDate;
                $payLoad->Invoices[1]->InvoiceOptionalFields = self::createOptionalFields($request);
                
                $sageRequestType = SageEnum::SRT_CREATE_AR_PREM_COMM_CORR_INV;
            }
        }

        return [
            'endPoint' => 'AR/ARInvoiceBatches',
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType,
            'entry_type' => $entryType
        ];
    }
    public static function createARInvoiceSplitPayments($request, $splitPayments)
    {
        // Payload creation logic for default scenario
        $taxClass = 1;
        if ($request->commissionIncludingVat > 0) {
            $taxClass = 1;
        } else {
            $taxClass = 2;
        }
        $payLoad = [
            'Invoices' => [
                [
                    'CustomerNumber' => $request->customerId,
                    'DocumentNumber' => $request->insurerPremiumNumber,
                    'InvoiceDescription' => $request->invoiceDescription.'-PREM',
                    'DocumentDate' => $request->insurerInvoiceDate,
                    'CurrencyCode' => 'AED',
                    'DueDate' => $request->paymentDueDate ?? null,
                    'TaxGroup' => 'VAT',
                    'TaxClass1' => 5,
                    'TaxAmount1' => 0.000,
                    'DocumentTotalBeforeTax' => $request->premiumWithoutTax,
                    'DocumentTotalIncludingTax' => $request->premiumWithTax,
                    'PostingDate' => $request->bookingDate,
                    'Terms' => 'SPLIT'.count($splitPayments),
                    'InvoiceDetails' => [
                        [
                            'Description' => $request->invoiceDescription,
                            'TaxClass1' => 5,
                            'RevenueAccount' => '55020',
                            'ExtendedAmountWithTIP' => $request->premiumWithTax,
                            'ExtendedAmountWithoutTIP' => $request->premiumWithoutTax,
                        ],
                    ],

                    'InvoicePaymentSchedules' => self::createPaymentSchedules($splitPayments),
                    'InvoiceOptionalFields' => self::createOptionalFields($request),
                ],
                [
                    'CustomerNumber' => $request->customerId,
                    'DocumentNumber' => $request->insurerCommissionNumber,
                    'InvoiceDescription' => $request->invoiceDescription.'-COM',
                    'DocumentDate' => $request->insurerInvoiceDate,
                    'CurrencyCode' => 'AED',
                    'DueDate' => $request->paymentDueDate ?? null,
                    'TaxGroup' => 'VAT',
                    'TaxClass1' => $taxClass,
                    'TaxAmount1' => $request->vatOnCommission,
                    'DocumentTotalBeforeTax' => $request->commission,
                    'DocumentTotalIncludingTax' => $request->commissionIncludingVat,
                    'PostingDate' => $request->bookingDate,
                    'InvoiceDetails' => [
                        [
                            'Description' => $request->invoiceDescription,
                            'TaxClass1' => $taxClass,
                            'TaxAmount1' => $request->vatOnCommission,
                            'RevenueAccount' => '60010',
                            'ExtendedAmountWithTIP' => $request->commissionIncludingVat,
                            'ExtendedAmountWithoutTIP' => $request->commission,
                        ],
                    ],
                    'InvoicePaymentSchedules' => [
                        [
                            'DueDate' => $request->paymentDueDate ?? null,
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

    public static function createPaymentSchedules($splitPayments)
    {
        $data = [];
        foreach ($splitPayments as $key => $item) {
            $temp['EntryNumber'] = 1;
            $temp['PaymentNumber'] = $key + 1;
            $temp['DueDate'] = date('Y-m-d', strtotime($item->due_date));
            $temp['AmountDue'] = $item->collection_amount === null ? 0 : $item->collection_amount;
            $data[] = $temp;
        }

        return $data;
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

    public static function readyToPostReceiptArPayment($batchNumber)
    {
        $payLoad = [
            'BatchStatus' => 'ReadyToPost',
        ];

        return [
            'endPoint' => 'AR/ARReceiptAndAdjustmentBatches'.'(BatchRecordType=\'CA\',BatchNumber='.$batchNumber.')',
            'payload' => $payLoad,
        ];
    }
    public static function aRPostReceiptsPayment($batchNumber)
    {
        $payLoad = [
            'BatchType' => 'CA',
            'PostAllBatches' => 'Donotpostallbatches',
            'PostBatchFrom' => $batchNumber,
            'PostBatchTo' => $batchNumber,
            'ActionSelector' => 'string',
            'UpdateOperation' => 'Unspecified',

        ];

        $sign = '$process';
        $val = "('".$sign."')";

        return [
            'endPoint' => 'AR/ARPostReceiptsAndAdjustments'.$val,
            'payload' => $payLoad,
        ];
    }

    public static function readyToPostReceiptAr($batchNumber)
    {
        $payLoad = [
            'BatchStatus' => 'ReadyToPost',

        ];

        return [
            'endPoint' => 'AR/ARReceiptAndAdjustmentBatches'.'(BatchRecordType=\'CA\',BatchNumber='.$batchNumber.')',
            'payload' => $payLoad,
        ];
    }

    public static function aRPostReceipts($batchNumber)
    {
        $payLoad = [
            'BatchType' => 'CA',
            'PostAllBatches' => 'Donotpostallbatches',
            'PostBatchFrom' => $batchNumber,
            'PostBatchTo' => $batchNumber,
            'ActionSelector' => 'string',
            'UpdateOperation' => 'Unspecified',

        ];

        $sign = '$process';
        $val = "('".$sign."')";

        return [
            'endPoint' => 'AR/ARPostReceiptsAndAdjustments'.$val,
            'payload' => $payLoad,
        ];
    }
    public static function readyToPostInvoiceAr($batchNumber, $type = SageEnum::SCT_STRAIGHT, $useFor = SageEnum::SCT_STRAIGHT)
    {
        $payLoad = [
            'BatchStatus' => 'ReadyToPost',
        ];

        $sageRequestType = SageEnum::SRT_RTP_AR_PREM_COMM_INV;
        $entryType = SageEnum::SCT_STRAIGHT;

        if (in_array($type, [SageEnum::SCT_REVERSAL, SageEnum::SCT_CORRECTION])) {

            $entryType = $type;
            $sageRequestType = ($type == SageEnum::SCT_REVERSAL) ? SageEnum::SRT_RTP_AR_PREM_COMM_REV_INV : SageEnum::SRT_RTP_AR_PREM_COMM_CORR_INV;

            if ($useFor == SageEnum::SCT_DISCOUNT) {
                $sageRequestType = ($type == SageEnum::SCT_REVERSAL) ? SageEnum::SRT_RTP_AR_DISC_REV_INV : SageEnum::SRT_RTP_AR_DISC_CORR_INV;
            }
        }

        return [
            'endPoint' => 'AR/ARInvoiceBatches'.'('.$batchNumber.')',
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType,
            'entry_type' => $entryType
        ];
    }

    public static function readyToPostInvoiceAP($batchNumber, $type = SageEnum::SCT_STRAIGHT)
    {
        $payLoad = [
            'BatchStatus' => 'ReadyToPost',
        ];

        $sageRequestType = SageEnum::SRT_RTP_AP_PREM_INV;
        $entryType = SageEnum::SCT_STRAIGHT;

        if (in_array($type, [SageEnum::SCT_REVERSAL, SageEnum::SCT_CORRECTION])) {
            $entryType = $type;
            $sageRequestType = ($type == SageEnum::SCT_REVERSAL) ? SageEnum::SRT_RTP_AP_PREM_REV_INV : SageEnum::SRT_RTP_AP_PREM_CORR_INV;
        }

        return [
            'endPoint' => 'AP/APInvoiceBatches'.'('.$batchNumber.')',
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType,
            'entry_type' => $entryType
        ];
    }

    public static function aPPostInvoices($batchNumber, $type = SageEnum::SCT_STRAIGHT)
    {
        $payLoad = [
            'ProcessAllBatches' => 'Donotpostallbatches',
            'FromBatch' => $batchNumber,
            'ToBatch' => $batchNumber,
            'ActionSelector' => 'string',
            'UpdateOperation' => 'Unspecified',

        ];

        $sign = '$process';
        $val = "('".$sign."')";

        $sageRequestType = SageEnum::SRT_POST_AP_PREM_INV;
        $entryType = SageEnum::SCT_STRAIGHT;

        if (in_array($type, [SageEnum::SCT_REVERSAL, SageEnum::SCT_CORRECTION])) {
            $entryType = $type;
            $sageRequestType = ($type == SageEnum::SCT_REVERSAL) ? SageEnum::SRT_POST_AP_PREM_REV_INV : SageEnum::SRT_POST_AP_PREM_CORR_INV;
        }

        return [
            'endPoint' => 'AP/APPostInvoices'.$val,
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType,
            'entry_type' => $entryType
        ];
    }

    public static function aRPostInvoices($batchNumber, $type = SageEnum::SCT_STRAIGHT, $useFor = SageEnum::SCT_STRAIGHT)
    {
        $payLoad = [
            'PostAllBatches' => 'Donotpostallbatches',
            'PostBatchFrom' => $batchNumber,
            'PostBatchTo' => $batchNumber,
            'ActionSelector' => 'string',
            'UpdateOperation' => 'Unspecified',
        ];

        $sign = '$process';
        $val = "('".$sign."')";

        $sageRequestType = SageEnum::SRT_POST_AR_PREM_COMM_INV;
        $entryType = SageEnum::SCT_STRAIGHT;

        if (in_array($type, [SageEnum::SCT_REVERSAL, SageEnum::SCT_CORRECTION])) {
            $entryType = $type;
            $sageRequestType = ($type == SageEnum::SCT_REVERSAL) ? SageEnum::SRT_POST_AR_PREM_COMM_REV_INV : SageEnum::SRT_POST_AR_PREM_COMM_CORR_INV;
            
            if ($useFor == SageEnum::SCT_DISCOUNT) {
                $sageRequestType = ($type == SageEnum::SCT_REVERSAL) ? SageEnum::SRT_POST_AR_DISC_REV_INV : SageEnum::SRT_POST_AR_DISC_CORR_INV;
            }
        }

        return [
            'endPoint' => 'AR/ARPostInvoices'.$val,
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType,
            'entry_type' => $entryType
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

    public static function arSplitPrepaymentPayload($quote, $sage_customer_number, $payment, $splitPayments)
    {
        $payLoad = [
            'BatchRecordType' => 'CA',
            'ReceiptsAdjustments' => [
                [
                    'BatchType' => 'CA',
                    'CustomerNumber' => $sage_customer_number,
                    'ReceiptTransactionType' => 'Receipt',
                    'AppliedReceiptsAdjustments' => self::createAppliedReceiptsAdjustments($quote, $sage_customer_number, $payment, $splitPayments),
                ],
            ],
        ];

        return [
            'endPoint' => 'AR/ARReceiptAndAdjustmentBatches',
            'payload' => $payLoad,
        ];
    }

    private static function createAppliedReceiptsAdjustments($quote, $sage_customer_number, $payment, $splitPayments)
    {
        $data = [];
        $data['BatchType'] = 'CA';
        $data['CustomerNumber'] = $sage_customer_number;
        $data['DocumentNumber'] = $payment->insurer_tax_number;
        $data['ReceiptTransactionType'] = 'Receipt';
        $data['CustomerReceiptAmount'] = floatval($quote->price_with_vat);

        foreach ($splitPayments as $key => $item) {
            $temp['BatchType'] = 'CA';
            $temp['CustomerNumber'] = $sage_customer_number;
            $temp['DocumentNumber'] = $item->sage_reciept_id;
            $temp['ReceiptTransactionType'] = 'Receipt';
            $temp['CustomerReceiptAmount'] = -$item->payment_amount;
            $data[] = $temp;
        }

        return $data;
    }

    public static function sagePayLoad($quoteType, $quote, $payment, $splitPayments) 
    {
        // Todo:: Need to confirm on this, those values which are fetching from quote is correct for Send update payment case?
        $response = [
            'discount' => floatval($payment->discount_value),
            'invoiceDescription' => $payment->invoice_description,
            'bookingDate' => date('Y-m-d', strtotime($quote['policy_booking_date'])),
            'policyExpiryDate' => date('Ymd', strtotime($quote['renewal_expiry_date'])),
            'insurerInvoiceDate' => date('Y-m-d', strtotime($payment->insurer_invoice_date)),
            'mainClassInsurance' => $quoteType,
            'policyNumber' => $quote->policy_number,
            'policyIssuer' => auth()->user()->name,
            'requestType' => Lookup::where('id', $quote->transaction_type_id)->first()->text ?? '',
            'subClass' => '',
            'invoicePaymentStatus' => $payment->transaction_payment_status,
            'advisorName' => !empty($quote->advisor_id) ? User::where('id', $quote->advisor_id)->value('name') : '',
            'premiumWithoutTax' => floatval($quote->price_without_vat),
            'premiumWithTax' => floatval($quote->price_with_vat),
            'vatOnCommission' => floatval($payment->commission_vat),
            'commission' => floatval($payment->commission),
            'commissionIncludingVat' => floatval($payment->commission_vat_applicable),
            'commissionWithOutVat' => $payment->commission_vat_not_applicable,
            'insurerPremiumNumber' => (string) $payment['insurer_tax_number'],
            'insurerCommissionNumber' => (string) $payment['insurer_commmission_invoice_number'],
        ];

        if(!empty($splitPayments)) {
            $response['paymentDueDate'] = date('Y-m-d', strtotime($splitPayments[0]['due_date']));
        }

        if (count($splitPayments) == 1) {
            $response['sage_reciept_id'] = $splitPayments[0]['sage_reciept_id'];
            $response['collection_amount'] = $splitPayments[0]['collection_amount'];
        }

        return (object) $response;
    }

    public static function getInvoiceDetails($invoiceType, $batchNumber)
    {

        $endPoint = ($invoiceType == SageEnum::SRT_GET_AR_INVOICE) ? 'AR/ARInvoiceBatches' : 'AP/APInvoiceBatches';

        return [
            'endPoint' => $endPoint.'('.$batchNumber.')',
            'sage_request_type' => $invoiceType,
            'entry_type' => $invoiceType,
        ];
    }

    public static function handleSageAPIsParms($apiType, $useFor = SageEnum::SCT_STRAIGHT)
    {
        $response = [];
        $message = self::getErrorMessages();

        switch ($apiType) {
            case SageEnum::SRT_CREATE_AR_PREM_COMM_INV:
                $response = [
                    'steps' => 3,
                    'recursiveCalls' => [
                        'createARInvoicePremAndComm',
                        'readyToPostInvoiceAr',
                        'aRPostInvoices'
                    ],
                    'extraDetails' => [
                        'createARInvoicePremAndComm' => [
                            'requestParms' => 'payload',
                            'nextCondition' => 'BatchNumber',
                            'errorMessage' => 'AR Invoice & prem failed from Sage'
                        ],
                        'readyToPostInvoiceAr' => [
                            'requestParms' => 'BatchNumber',
                            'verb' => 'PATCH',
                            'logResponse' => true,
                            'conditionChecks' => ['type' => 'Not Empty', 'condtion_to_check' => ''],
                            'errorMessage' => 'Error while making AR Invoice & prem ready to post to Sage'
                        ],
                        'aRPostInvoices' => [
                            'requestParms' => 'BatchNumber',
                            'logResponse' => true,
                            'conditionChecks' => ['type' => 'isset', 'condtion_to_check' => 'error'],
                            'errorMessage' => 'Error while making AR Invoice & prem Posted to Sage'
                        ]
                    ]
                ];
                break;

            case SageEnum::SRT_CREATE_AP_PREM_INV:
                $response = [
                    'steps' => 3,
                    'recursiveCalls' => [
                        'createAPInvoicePrem',
                        'readyToPostInvoiceAP',
                        'aPPostInvoices'
                    ],
                    'extraDetails' => [
                        'createAPInvoicePrem' => [
                            'requestParms' => 'payload',
                            'nextCondition' => 'BatchNumber',
                            'errorMessage' => 'AP Invoice prem failed from Sage'
                        ],
                        'readyToPostInvoiceAP' => [
                            'requestParms' => 'BatchNumber',
                            'verb' => 'PATCH',
                            'logResponse' => true,
                            'conditionChecks' => ['type' => 'Not Empty', 'condtion_to_check' => ''],
                            'errorMessage' => 'Error while making AP Invoice ready to post to Sage'
                        ],
                        'aPPostInvoices' => [
                            'requestParms' => 'BatchNumber',
                            'logResponse' => true,
                            'conditionChecks' => ['type' => 'isset', 'condtion_to_check' => 'error'],
                            'errorMessage' => 'Error while making AP Invoice posted to Sage'
                        ]
                    ]
                ];
                break;

            case SageEnum::SRT_CREATE_AR_DISC_INV:
                $response = [
                    'steps' => 3,
                    'recursiveCalls' => [
                        'createARInvoiceDis',
                        'readyToPostInvoiceAr',
                        'aRPostInvoices'
                    ],
                    'extraDetails' => [
                        'createARInvoiceDis' => [
                            'requestParms' => 'payload',
                            'nextCondition' => 'BatchNumber',
                            'errorMessage' => 'AR discount Invoice failed from Sage'
                        ],
                        'readyToPostInvoiceAr' => [
                            'requestParms' => 'BatchNumber',
                            'verb' => 'PATCH',
                            'logResponse' => true,
                            'conditionChecks' => ['type' => 'Not Empty', 'condtion_to_check' => ''],
                            'errorMessage' => 'Error while making AR discount Invoice ready to post to Sage'
                        ],
                        'aRPostInvoices' => [
                            'requestParms' => 'BatchNumber',
                            'logResponse' => true,
                            'conditionChecks' => ['type' => 'isset', 'condtion_to_check' => 'error'],
                            'errorMessage' => 'Error while making AR discount Invoice posted to Sage'
                        ]
                    ]
                ];
                break;

            case SageEnum::SRT_CREATE_AR_SPPAY_INV:
                $response = [
                    'recursiveCalls' => [
                        'createPaymontRecieptOneInvoice',
                        'readyToPostReceiptArPayment',
                        'aRPostReceipts',
                        'arSplitPrepaymentPayload',
                    ],
                    'extraDetails' => [
                        'createPaymontRecieptOneInvoice' => [
                            'requestParms' => 'payload',
                            'nextCondition' => 'BatchNumber',
                            'errorMessage' => 'Apply payment failed from Sage'
                        ],
                        'readyToPostReceiptArPayment' => [
                            'requestParms' => 'sageCustomerNumber',
                            'logResponse' => true,
                            'conditionChecks' => ['type' => 'Not Empty', 'condtion_to_check' => ''],
                            'errorMessage' => 'Error while posting to Payment'
                        ],
                        'aRPostReceipts' => [
                            'requestParms' => 'BatchNumber',
                            'logResponse' => true,
                            'conditionChecks' => ['type' => 'isset', 'condtion_to_check' => 'error'],
                            'errorMessage' => 'Error while making Apply payment posted to Sage'
                        ],
                        'arSplitPrepaymentPayload' => [
                            'requestParms' => 'sageCustomerNumber',
                            'logResponse' => true,
                            'conditionChecks' => ['type' => 'Not Empty', 'condtion_to_check' => ''],
                            'errorMessage' => 'Error while making Apply payment ready to post to Sage'
                        ]
                    ]
                ];
                break;

            case SageEnum::SRT_REV_CORR_AR_PREM_COMM_INV:
                $response = [
                    'recursiveCalls' => [
                        'getInvoiceDetails',
                        'createARInvoicePremAndComm',
                        'readyToPostInvoiceAr',
                        'aRPostInvoices',
                    ],
                    'extraDetails' => [
                        'getInvoiceDetails' => [
                            'verb' => 'GET',
                        ],
                        'createARInvoicePremAndComm' => [
                            'requestParms' => 'payload',
                            'nextCondition' => 'BatchNumber',
                            'errorMessage' => $message[$apiType][$useFor]['createARInvoicePremAndComm']['error']
                        ],
                        'readyToPostInvoiceAr' => [
                            'requestParms' => 'BatchNumber',
                            'verb' => 'PATCH',
                            'logResponse' => true,
                            'conditionChecks' => ['type' => 'Not Empty', 'condtion_to_check' => ''],
                            'errorMessage' => $message[$apiType][$useFor]['readyToPostInvoiceAr']['error']
                        ],
                        'aRPostInvoices' => [
                            'requestParms' => 'BatchNumber',
                            'logResponse' => true,
                            'conditionChecks' => ['type' => 'isset', 'condtion_to_check' => 'error'],
                            'errorMessage' => $message[$apiType][$useFor]['aRPostInvoices']['error']
                        ]
                    ]
                ];
        }

        return $response;
    }

    public static function getErrorMessages()
    {
        $message = [
            SageEnum::SRT_REV_CORR_AR_PREM_COMM_INV => [
                SageEnum::SCT_STRAIGHT => [
                    'createARInvoicePremAndComm' => [
                        'error' => 'AR Invoice & prem failed from Sage'
                    ],
                    'readyToPostInvoiceAr' => [
                        'error' => 'Error while making AR Invoice & prem ready to post to Sage'
                    ],
                    'aRPostInvoices' => [
                        'error' => 'Error while making AR Invoice & prem Posted to Sage'
                    ]
                ], 
                SageEnum::SCT_REVERSAL => [
                    'createARInvoicePremAndComm' => [
                        'error' => 'AR Invoice & prem reversal failed from Sage'
                    ],
                    'readyToPostInvoiceAr' => [
                        'error' => 'Error while making AR Invoice & prem reversal ready to post to Sage'
                    ],
                    'aRPostInvoices' => [
                        'error' => 'Error while making AR Invoice & prem reversal Posted to Sage'
                    ]
                ],
                SageEnum::SCT_CORRECTION => [
                    'createARInvoicePremAndComm' => [
                        'error' => 'AR Invoice & prem correction failed from Sage'
                    ],
                    'readyToPostInvoiceAr' => [
                        'error' => 'Error while making AR Invoice & prem correction ready to post to Sage'
                    ],
                    'aRPostInvoices' => [
                        'error' => 'Error while making AR Invoice & prem correction Posted to Sage'
                    ]
                ]
            ],
            SageEnum::SRT_CREATE_AP_PREM_INV => [
                SageEnum::SCT_STRAIGHT => [
                    'createAPInvoicePrem' => [
                        'error' => 'AP Invoice prem failed from Sage'
                    ],
                    'readyToPostInvoiceAP' => [
                        'error' => 'Error while making AP Invoice ready to post to Sage'
                    ],
                    'aPPostInvoices' => [
                        'error' => 'Error while making AP Invoice posted to Sage'
                    ]
                ], 
                SageEnum::SCT_REVERSAL => [
                    'createAPInvoicePrem' => [
                        'error' => 'AP Invoice prem reversal failed from Sage'
                    ],
                    'readyToPostInvoiceAP' => [
                        'error' => 'Error while making AP Invoice reversal ready to post to Sage'
                    ],
                    'aPPostInvoices' => [
                        'error' => 'Error while making AP Invoice reversal posted to Sage'
                    ]
                ],
                SageEnum::SCT_CORRECTION => [
                    'createAPInvoicePrem' => [
                        'error' => 'AP Invoice prem correction failed from Sage'
                    ],
                    'readyToPostInvoiceAP' => [
                        'error' => 'Error while making AP Invoice correction ready to post to Sage'
                    ],
                    'aPPostInvoices' => [
                        'error' => 'Error while making AP Invoice correction posted to Sage'
                    ]
                ]
            ],
            SageEnum::SRT_CREATE_AR_DISC_INV => [
                SageEnum::SCT_STRAIGHT => [
                    'createARInvoiceDis' => [
                        'error' => 'AR discount Invoice failed from Sage'
                    ],
                    'readyToPostInvoiceAr' => [
                        'error' => 'Error while making AR discount Invoice ready to post to Sage'
                    ],
                    'aRPostInvoices' => [
                        'error' => 'Error while making AR discount Invoice posted to Sage'
                    ]
                ], 
                SageEnum::SCT_REVERSAL => [
                    'createARInvoiceDis' => [
                        'error' => 'AR discount Invoice reversal failed from Sage'
                    ],
                    'readyToPostInvoiceAr' => [
                        'error' => 'Error while making AR discount Invoice reversal ready to post to Sage'
                    ],
                    'aRPostInvoices' => [
                        'error' => 'Error while making AR discount Invoice reversal posted to Sage'
                    ]
                ],
                SageEnum::SCT_CORRECTION => [
                    'createARInvoiceDis' => [
                        'error' => 'AR discount Invoice correction failed from Sage'
                    ],
                    'readyToPostInvoiceAr' => [
                        'error' => 'Error while making AR discount Invoice correction ready to post to Sage'
                    ],
                    'aRPostInvoices' => [
                        'error' => 'Error while making AR discount Invoice correction posted to Sage'
                    ]
                ]
            ],
        ];

        return $message;
    }

}
