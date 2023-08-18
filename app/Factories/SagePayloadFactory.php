<?php
namespace App\Factories;

class SagePayloadFactory
{
    public static function createPayload($request, $leadStatus)
    {
        // Logic to create different payloads based on request and leadStatus
        if (strtolower($request->invoicePaymentStatus) == 'paid' && $leadStatus == "policy booked") {
            return self::createApplyDocumentPayload();
        } elseif ($request->discount > 0 && $leadStatus == "policy booked") {
            return self::createCreditNotePayload();
        } else {
            return self::createDefaultPayload($request);
        }
    }

    private static function createApplyDocumentPayload()
    {
        $payLoad = [
            "BatchRecordType" => "CA",
            "ReceiptsAdjustments" => [
                [
                    "BatchType" => "CA",
                    "CustomerNumber" => "C00018",
                    "ReceiptTransactionType" => "ApplyDocument",
                    "DocumentNumber" => "PY000020",
                    "AppliedReceiptsAdjustments" => [
                        [
                            "BatchType" => "CA",
                            "CustomerNumber" => "C00018",
                            "DocumentNumber" => "SHMOU22000124845",
                            "ReceiptTransactionType" => "ApplyDocument",
                        ],
                    ],
                ],
            ],
        ];
        return [
            'endPoint' => "AR/ARReceiptAndAdjustmentBatches",
            'payload' => $payLoad,
        ];
    }


    private static function createCreditNotePayload()
    {
        // Payload creation logic for CreditNote scenario
        $payLoad = $data = [
            "Invoices" => [
                [
                    "CustomerNumber" => "IC008",
                    "DocumentNumber" => "12333344345-DIS",
                    "InvoiceDescription" => "D.ORIUNB.PL.P-10-1002-109-2022-109",
                    "DocumentDate" => "2023-05-16T00:00:00Z",
                    "DocumentType" => "CreditNote",
                    "CurrencyCode" => "AED",
                    "DueDate" => "2023-05-26T00:00:00Z",
                    "ApplytoDocument" => "",
                    "TaxGroup" => "VAT",
                    "TaxClass1" => 5,
                    "DocumentTotalBeforeTax" => 5715.69,
                    "DocumentTotalIncludingTax" => 5715.69,
                    "PostingDate" => "2023-04-26T00:00:00Z",
                    "InvoiceDetails" => [
                        [
                            "Description" => "D.ORIUNB.PL.P-10-1002-109-2022-109",
                            "TaxClass1" => 5,
                            "RevenueAccount" => "70010",
                            "ExtendedAmountWithTIP" => 5715.69,
                            "ExtendedAmountWithoutTIP" => 5715.69,
                        ],
                    ],
                    "InvoicePaymentSchedules" => [
                        [
                            "DueDate" => "2023-05-16T00:00:00Z",
                        ],
                    ],
                    "InvoiceOptionalFields" => [
                        [
                            "OptionalField" => "CCCODE",
                            "Value" => "sample cc code",
                        ],
                        [
                            "OptionalField" => "ENDORSEMENT",
                            "Value" => "sample endorsement number",
                        ],
                        [
                            "OptionalField" => "EXPIRY",
                            "Value" => "20230505",
                        ],
                        [
                            "OptionalField" => "INCEPTION",
                            "Value" => "20230505",
                        ],
                        [
                            "OptionalField" => "INSURED",
                            "Value" => "sample insured",
                        ],
                        [
                            "OptionalField" => "MAINCLASS",
                            "Value" => "sample mainclass",
                        ],
                        [
                            "OptionalField" => "MANAGER",
                            "Value" => "sample manager",
                        ],
                        [
                            "OptionalField" => "PDC",
                            "Value" => "0",
                        ],
                        [
                            "OptionalField" => "POLICY",
                            "Value" => "sample policy number",
                        ],
                        [
                            "OptionalField" => "POLICYHOLDER",
                            "Value" => "sample policy holder",
                        ],
                        [
                            "OptionalField" => "POLICYISSUER",
                            "Value" => "sample policy issuer",
                        ],
                        [
                            "OptionalField" => "PREMIUM",
                            "Value" => "0.000",
                        ],
                        [
                            "OptionalField" => "PREMIUMVAT",
                            "Value" => "0.000",
                        ],
                        [
                            "OptionalField" => "REQUESTTYPE",
                            "Value" => "sample request type",
                        ],
                        [
                            "OptionalField" => "SALESPERSON",
                            "Value" => "sample salesperson",
                        ],
                        [
                            "OptionalField" => "SUBCLASS",
                            "Value" => "sample subclass",
                        ],
                        [
                            "OptionalField" => "CNTYPE",
                            "Value" => "Normal",
                        ],
                        [
                            "OptionalField" => "COLLECTS",
                            "Value" => "",
                        ],
                        [
                            "OptionalField" => "COMMRATE",
                            "Value" => "",
                        ],
                        [
                            "OptionalField" => "STATE",
                            "Value" => "DXB",
                        ],
                    ],
                ],
            ],
        ];
        return [
            'endPoint' => "AR/ARInvoiceBatches",
            'payload' => $payLoad,
        ];
    }

    private static function createDefaultPayload($request)
    {
        // Payload creation logic for default scenario
        $payLoad = [
            "Invoices" => [
                [
                    "CustomerNumber" => "C1111",
                    "DocumentNumber" => $request->insurerPremiumTaxInvoiceNumber,
                    "InvoiceDescription" => "HAFEEZ SAMPLE 2",
                    "DocumentDate" => "2023-04-26T00:00:00Z",
                    "CurrencyCode" => "AED",
                    "DueDate" => "2023-04-26T00:00:00Z",
                    "TaxGroup" => "VAT",
                    "TaxClass1" => 5,
                    "TaxAmount1" => 0.000,
                    "DocumentTotalBeforeTax" => 3000,
                    "DocumentTotalIncludingTax" => 3000,
                    "PostingDate" => "2023-04-26T00:00:00Z",
                    "InvoiceDetails" => [
                        [
                            "Description" => "HAFEEZ SAMPLE DESC",
                            "TaxClass1" => 5,
                            "RevenueAccount" => "55020",
                            "ExtendedAmountWithTIP" => 4000,
                            "ExtendedAmountWithoutTIP" => 3500,
                        ],
                    ],
                    "InvoicePaymentSchedules" => [
                        [
                            "DueDate" => "2023-04-26T00:00:00Z",
                        ],
                    ],
                    "InvoiceOptionalFields" => [
                        [
                            "OptionalField" => "CCCODE",
                            "Value" => "sample cc code",
                        ],
                        [
                            "OptionalField" => "ENDORSEMENT",
                            "Value" => "sample endorsement number",
                        ],
                        [
                            "OptionalField" => "EXPIRY",
                            "Value" => "20230505",
                        ],
                        [
                            "OptionalField" => "INCEPTION",
                            "Value" => "20230505",
                        ],
                        [
                            "OptionalField" => "INSURED",
                            "Value" => "sample insured",
                        ],
                        [
                            "OptionalField" => "MAINCLASS",
                            "Value" => "sample mainclass",
                        ],
                        [
                            "OptionalField" => "MANAGER",
                            "Value" => "sample manager",
                        ],
                        [
                            "OptionalField" => "PDC",
                            "Value" => "0",
                        ],
                        [
                            "OptionalField" => "POLICY",
                            "Value" => "sample policy number",
                        ],
                        [
                            "OptionalField" => "POLICYHOLDER",
                            "Value" => "sample policy holder",
                        ],
                        [
                            "OptionalField" => "POLICYISSUER",
                            "Value" => "sample policy issuer",
                        ],
                        [
                            "OptionalField" => "PREMIUM",
                            "Value" => "0.000",
                        ],
                        [
                            "OptionalField" => "PREMIUMVAT",
                            "Value" => "0.000",
                        ],
                        [
                            "OptionalField" => "REQUESTTYPE",
                            "Value" => "sample request type",
                        ],
                        [
                            "OptionalField" => "SALESPERSON",
                            "Value" => "sample salesperson",
                        ],
                        [
                            "OptionalField" => "SUBCLASS",
                            "Value" => "sample subclass",
                        ],
                        [
                            "OptionalField" => "CNTYPE",
                            "Value" => "Normal",
                        ],
                        [
                            "OptionalField" => "COLLECTS",
                            "Value" => "",
                        ],
                        [
                            "OptionalField" => "COMMRATE",
                            "Value" => "",
                        ],
                        [
                            "OptionalField" => "STATE",
                            "Value" => "DXB",
                        ],
                    ],
                ],
            ],
        ];
        return [
            'endPoint' => "AR/ARInvoiceBatches",
            'payload' => $payLoad,
        ];
    }
}