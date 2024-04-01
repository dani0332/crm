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
                    'ReceiptTransactionType' => 'Prepayment',
                    'DocumentNumber' => $request->sage_reciept_id,
                    'AppliedReceiptsAdjustments' => [
                        [
                            'BatchType' => 'CA',
                            'CustomerNumber' => $request->customerId,
                            'DocumentNumber' => $request->insurerPremiumTaxInvoiceNumber,
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

    public static function createAPInvoicePrem($request)
    {

        $payLoad = [
            'Invoices' => [
                [
                    'VendorNumber' => 'IP002', // use vender api to create vender in sage

                    'DocumentNumber' => $request->insurerPremiumTaxInvoiceNumber,
                    'InvoiceDescription' => $request->invoiceDescription,
                    'DocumentDate' => $request->insurerInvoiceDate,
                    'CurrencyCode' => 'AED', // alway will be AED discussed with denber
                    'DueDate' => $request->paymentDueDate,
                    'TaxGroup' => 'VAT', // alway will be VAT discussed with denber
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
        $payLoad = [
            'Invoices' => [
                [
                    'CustomerNumber' => $request->customerId,
                    'DocumentNumber' => $request->insurerPremiumTaxInvoiceNumber.'-DIS',
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
        $payLoad = [
            'Invoices' => [
                [
                    'CustomerNumber' => $request->customerId,
                    'DocumentNumber' => $request->insurerPremiumTaxInvoiceNumber,
                    'InvoiceDescription' => $request->invoiceDescription.'-PREM',
                    'DocumentDate' => '2023-04-26T00:00:00Z',
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
                    'DocumentNumber' => $request->insurerTaxInvoiceNumber,
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
            'endPoint' => 'AR/ARReceiptAndAdjustmentBatches'.'(BatchRecordType=CA,BatchNumber='.$batchNumber.')',
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
            'endPoint' => 'AR/ARReceiptAndAdjustmentBatches'.$val,
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
