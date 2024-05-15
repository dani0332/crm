<?php

namespace App\Factories;

use App\Enums\PaymentMethodsEnum;
use App\Enums\SagePaymentMethodsEnum;
use App\Models\QuoteRequestEntityMapping;

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

    public static function createAPInvoicePrem($request)
    {
        $premiumDescription = 'P.'.$request->invoiceDescription;
        $payLoad = [
            'Invoices' => [
                [
                    'VendorNumber' => $request->sageVenderId, // use vender api to create vender in sage

                    'DocumentNumber' => $request->insurerPremiumNumber,
                    'InvoiceDescription' => $premiumDescription,
                    'DocumentDate' => $request->insurerInvoiceDate,
                    'CurrencyCode' => 'AED', // alway will be AED discussed with denber
                    'DueDate' => $request->paymentDueDate,
                    'TaxGroup' => 'VAT', // alway will be VAT discussed with denber
                    'TaxClass1' => 5,
                    'TaxAmount1' => 0.000,
                    'DocumentTotalBeforeTaxes' => $request->totalAmount,
                    'DocumentTotalIncludingTax' => $request->totalAmount,
                    'PostingDate' => $request->bookingDate,
                    'InvoiceDetails' => [
                        [
                            'DistributionDescription' => $premiumDescription,
                            'TaxClass1' => 5,
                            'GLAccount' => $request->insurerGlLiaiblityAccount,
                            'DistributedAmount' => $request->totalAmount,
                            'DistributedAmountBeforeTaxes' => $request->totalAmount,
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

        return [
            'endPoint' => 'AP/APInvoiceBatches',
            'payload' => $payLoad,
        ];
    }

    public static function createARInvoiceDis($request)
    {
        // Payload creation logic for CreditNote scenario
        $description = 'D.'.$request->invoiceDescription;
        $payLoad = [
            'Invoices' => [
                [
                    'CustomerNumber' => $request->customerId,
                    'DocumentNumber' => $request->insurerPremiumNumber.'-DIS',
                    'InvoiceDescription' => $description,
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
                            'Description' => $description,
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

        return [
            'endPoint' => 'AR/ARInvoiceBatches',
            'payload' => $payLoad,
        ];
    }

    public static function createARInvoicePremAndComm($request)
    {
        // Payload creation logic for default scenario
        $taxClass = 1;
        if ($request->commissionIncludingVat > 0) {
            $taxClass = 1;
        } else {
            $taxClass = 2;
        }
        $premiumDescription = 'P.'.$request->invoiceDescription;
        $commissionDescription = 'C.'.$request->invoiceDescription;
        $premiumWithDiscount = $request->totalAmount + $request->discount;
        $payLoad = [
            'Invoices' => [
                [
                    'CustomerNumber' => $request->customerId,
                    'DocumentNumber' => $request->insurerPremiumNumber,
                    'InvoiceDescription' => $premiumDescription,
                    'DocumentDate' => $request->insurerInvoiceDate,
                    'CurrencyCode' => 'AED',
                    'DueDate' => $request->paymentDueDate,
                    'TaxGroup' => 'VAT',
                    'TaxClass1' => 5,
                    'TaxAmount1' => 0.000,
                    'DocumentTotalBeforeTax' => $premiumWithDiscount,
                    'DocumentTotalIncludingTax' => $premiumWithDiscount,
                    'PostingDate' => $request->bookingDate,
                    'InvoiceDetails' => [
                        [
                            'Description' => $premiumDescription,
                            'TaxClass1' => 5,
                            'RevenueAccount' => $request->insurerGlLiaiblityAccount,
                            'ExtendedAmountWithTIP' => $premiumWithDiscount,
                            'ExtendedAmountWithoutTIP' => $premiumWithDiscount,
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
                    'InvoiceDescription' => $commissionDescription,
                    'DocumentDate' => $request->insurerInvoiceDate,
                    'CurrencyCode' => 'AED',
                    'DueDate' => $request->paymentDueDate,
                    'TaxGroup' => 'VAT',
                    'TaxClass1' => $taxClass,
                    'TaxAmount1' => $request->vatOnCommission,
                    'DocumentTotalBeforeTax' => $request->commissionIncludingVat > 0 ? $request->commissionIncludingVat : $request->commissionWithOutVat,
                    'DocumentTotalIncludingTax' => $request->commission,
                    'PostingDate' => $request->bookingDate,
                    'InvoiceDetails' => [
                        [
                            'Description' => $commissionDescription,
                            'TaxClass1' => $taxClass,
                            'TaxAmount1' => $request->vatOnCommission,
                            'RevenueAccount' => '60010',
                            'ExtendedAmountWithTIP' => $request->commission,
                            'ExtendedAmountWithoutTIP' => $request->commissionIncludingVat > 0 ? $request->commissionIncludingVat : $request->commissionWithOutVat,
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

        return [
            'endPoint' => 'AR/ARInvoiceBatches',
            'payload' => $payLoad,
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
        $premiumDescription = 'P.'.$request->invoiceDescription;
        $commissionDescription = 'C.'.$request->invoiceDescription;
        $payLoad = [
            'Invoices' => [
                [
                    'CustomerNumber' => $request->customerId,
                    'DocumentNumber' => $request->insurerPremiumNumber,
                    'InvoiceDescription' => $premiumDescription,
                    'DocumentDate' => $request->insurerInvoiceDate,
                    'CurrencyCode' => 'AED',
                    'DueDate' => $request->paymentDueDate ?? null,
                    'TaxGroup' => 'VAT',
                    'TaxClass1' => 5,
                    'TaxAmount1' => 0.000,
                    'DocumentTotalBeforeTax' => $request->premiumWithTax,
                    'DocumentTotalIncludingTax' => $request->premiumWithTax,
                    'PostingDate' => $request->bookingDate,
                    'Terms' => 'SPLIT'.count($splitPayments),
                    'InvoiceDetails' => [
                        [
                            'Description' => $premiumDescription,
                            'TaxClass1' => 5,
                            'RevenueAccount' => $request->insurerGlLiaiblityAccount,
                            'ExtendedAmountWithTIP' => $request->premiumWithTax,
                            'ExtendedAmountWithoutTIP' => $request->premiumWithTax,
                        ],
                    ],

                    'InvoicePaymentSchedules' => self::createPaymentSchedules($splitPayments),
                    'InvoiceOptionalFields' => self::createOptionalFields($request),
                ],
                [
                    'CustomerNumber' => $request->customerId,
                    'DocumentNumber' => $request->insurerCommissionNumber,
                    'InvoiceDescription' => $commissionDescription,
                    'DocumentDate' => $request->insurerInvoiceDate,
                    'CurrencyCode' => 'AED',
                    'DueDate' => $request->paymentDueDate ?? null,
                    'TaxGroup' => 'VAT',
                    'TaxClass1' => $taxClass,
                    'TaxAmount1' => $request->vatOnCommission,
                    'DocumentTotalBeforeTax' => $request->commissionIncludingVat > 0 ? $request->commissionIncludingVat : $request->commissionWithOutVat,
                    'DocumentTotalIncludingTax' => $request->commissionIncludingVat > 0 ? $request->commissionIncludingVat : $request->commissionWithOutVat,
                    'PostingDate' => $request->bookingDate,
                    'InvoiceDetails' => [
                        [
                            'Description' => $commissionDescription,
                            'TaxClass1' => $taxClass,
                            'TaxAmount1' => $request->vatOnCommission,
                            'RevenueAccount' => '60010',
                            'ExtendedAmountWithTIP' => $request->commissionIncludingVat > 0 ? $request->commissionIncludingVat : $request->commissionWithOutVat,
                            'ExtendedAmountWithoutTIP' => $request->commissionIncludingVat > 0 ? $request->commissionIncludingVat : $request->commissionWithOutVat,
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
                    'CheckReceiptNumber' => $request->checkDetails,
                    'PaymentCode' => self::sagePaymentCodeMapping($request->sage_payment_code),
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
    public static function readyToPostInvoiceAr($batchNumber)
    {
        $payLoad = [
            'BatchStatus' => 'ReadyToPost',

        ];

        return [
            'endPoint' => 'AR/ARInvoiceBatches'.'('.$batchNumber.')',
            'payload' => $payLoad,
        ];
    }

    public static function readyToPostInvoiceAP($batchNumber)
    {
        $payLoad = [
            'BatchStatus' => 'ReadyToPost',

        ];

        return [
            'endPoint' => 'AP/APInvoiceBatches'.'('.$batchNumber.')',
            'payload' => $payLoad,
        ];
    }

    public static function aPPostInvoices($batchNumber)
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

        return [
            'endPoint' => 'AP/APPostInvoices'.$val,
            'payload' => $payLoad,
        ];
    }

    public static function aRPostInvoices($batchNumber)
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

        return [
            'endPoint' => 'AR/ARPostInvoices'.$val,
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
                'Value' => $request->ccCode,
            ],
            [
                'OptionalField' => 'ENDORSEMENT',
                'Value' => $request->endorsementNumber,
            ],
            [
                'OptionalField' => 'EXPIRY',
                'Value' => $request->policyExpiryDate,
            ],
            [
                'OptionalField' => 'INCEPTION',
                'Value' => $request->policyBookingDate,
            ],
            [
                'OptionalField' => 'INSURED',
                'Value' => $request->insured,
            ],
            [
                'OptionalField' => 'MAINCLASS',
                'Value' => $request->mainClassInsurance,
            ],
            [
                'OptionalField' => 'MANAGER',
                'Value' => $request->manager,
            ],
            [
                'OptionalField' => 'PDC',
                'Value' => $request->isPostDatedCheck,
            ],
            [
                'OptionalField' => 'POLICY',
                'Value' => $request->policyNumber,
            ],
            [
                'OptionalField' => 'POLICYHOLDER',
                'Value' => $request->policyHolder,
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
                'Value' => $request->vatOnPremium,
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
                'Value' => $request->premiumCollectedBy,
            ],
            [
                'OptionalField' => 'COMMRATE',
                'Value' => $request->commissionPercentage,
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

        foreach ($splitPayments as $key => $item) {

            $receiptData['BatchType'] = 'CA';
            $receiptData['CustomerNumber'] = $sage_customer_number;
            $receiptData['DocumentNumber'] = $payment->insurer_tax_number;
            $receiptData['PaymentNumber'] = $key + 1;
            $receiptData['ReceiptTransactionType'] = 'Receipt';
            $receiptData['CustomerReceiptAmount'] = floatval($item->payment_amount);
            $data[] = $receiptData;

            $prePaymentData['BatchType'] = 'CA';
            $prePaymentData['CustomerNumber'] = $sage_customer_number;
            $prePaymentData['DocumentNumber'] = $item->sage_reciept_id;
            $prePaymentData['PaymentNumber'] = 1;
            $prePaymentData['ReceiptTransactionType'] = 'Receipt';
            $prePaymentData['CustomerReceiptAmount'] = -$item->payment_amount;
            $data[] = $prePaymentData;
        }

        return $data;
    }

    // Payment code mapping
    private static function sagePaymentCodeMapping($paymentMethod)
    {
        $sagePaymentCodeMappingArray = [
            PaymentMethodsEnum::BankTransfer => SagePaymentMethodsEnum::SAGE_BANK_TRANSFER,
            PaymentMethodsEnum::Cash => SagePaymentMethodsEnum::SAGE_CASH,
            PaymentMethodsEnum::Cheque => SagePaymentMethodsEnum::SAGE_CHEQUE,
            PaymentMethodsEnum::PostDatedCheque => SagePaymentMethodsEnum::SAGE_POST_DATED_CHEQUE,
            PaymentMethodsEnum::CreditCard => SagePaymentMethodsEnum::SAGE_CREDIT_CARD,
            PaymentMethodsEnum::InsurerPayment => SagePaymentMethodsEnum::SAGE_INSURER_PAYMENT,
        ];
        if (array_key_exists($paymentMethod, $sagePaymentCodeMappingArray)) {
            return $sagePaymentCodeMappingArray[$paymentMethod];
        } else {
            return SagePaymentMethodsEnum::SAGE_BANK_TRANSFER;
        }
    }
}
