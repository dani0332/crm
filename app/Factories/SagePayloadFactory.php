<?php

namespace App\Factories;

use App\Enums\PaymentMethodsEnum;
use App\Enums\quoteStatusCode;
use App\Enums\SageEnum;
use App\Enums\SagePaymentMethodsEnum;
use App\Models\BusinessInsuranceType;
use App\Models\Lookup;
use App\Models\QuoteRequestEntityMapping;
use App\Models\User;
use App\Repositories\SendUpdateLogRepository;
use Carbon\Carbon;

class SagePayloadFactory
{
    public static function instanceData()
    {
        return (object) [
            'sage_api_date_format' => env('SAGE_300_API_DATE_FORMAT'),
        ];
    }

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
        $entryType = SageEnum::SCT_STRAIGHT;
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
                            'CustomerReceiptAmount' => roundNumber($request->premiumWithTax),
                        ],
                    ],
                ],
            ],
        ];

        return [
            'endPoint' => 'AR/ARReceiptAndAdjustmentBatches',
            'payload' => $payLoad,
            'sage_request_type' => SageEnum::SRT_CREATE_PAY_REC_ONE_INV,
            'entry_type' => $entryType,
        ];
    }

    public static function createAPInvoicePrem($request, $type = SageEnum::SCT_STRAIGHT, $revCorrDetails = '')
    {
        $premiumDescription = 'P.'.$request->invoiceDescription;
        $payLoad = [
            'Invoices' => [
                [
                    'VendorNumber' => $request->sageVenderId, // use vender api to create vender in sage
                    'DocumentNumber' => $request->insurerPremiumNumber,
                    'InvoiceDescription' => $premiumDescription,
                    'DocumentDate' => Carbon::parse($request->insurerInvoiceDate)->format(self::instanceData()->sage_api_date_format), // Add date format because caught an error while calling sage for Send update
                    'CurrencyCode' => 'AED', // alway will be AED discussed with denber
                    'DueDate' => Carbon::parse($request->paymentDueDate)->format(self::instanceData()->sage_api_date_format),
                    'TaxGroup' => 'VAT', // alway will be VAT discussed with denber
                    'TaxClass1' => 5,
                    'TaxAmount1' => 0.000,
                    'DocumentTotalBeforeTaxes' => roundNumber($request->totalAmount),
                    'DocumentTotalIncludingTax' => roundNumber($request->totalAmount),
                    'PostingDate' => Carbon::parse($request->bookingDate)->format(self::instanceData()->sage_api_date_format), // Add date format because caught an error while calling sage for Send update
                    'InvoiceDetails' => [
                        [
                            'DistributionDescription' => $premiumDescription,
                            'TaxClass1' => 5,
                            'GLAccount' => $request->insurerGlLiaiblityAccount,
                            'DistributedAmount' => roundNumber($request->totalAmount),
                            'DistributedAmountBeforeTaxes' => roundNumber($request->totalAmount),
                        ],
                    ],
                    'InvoicePaymentSchedules' => [
                        [
                            'DueDate' => Carbon::parse($request->paymentDueDate)->format(self::instanceData()->sage_api_date_format),
                        ],
                    ],
                    'InvoiceOptionalFields' => self::createOptionalFields($request),
                ],
            ],
        ];

        $sageRequestType = SageEnum::SRT_CREATE_AP_PREM_INV;
        $entryType = SageEnum::SCT_STRAIGHT;

        // Payload update logic for reversal and correction scenario
        if (in_array($type, [SageEnum::SCT_REVERSAL, SageEnum::SCT_CORRECTION]) && ! empty($revCorrDetails)) {
            $entryType = $type;
            $payLoad = json_decode($revCorrDetails);

            $applyToDocument = $payLoad->Invoices[0]->DocumentNumber;
            unset($payLoad->BatchStatus);

            if ($type == SageEnum::SCT_REVERSAL) {

                $payLoad->Invoices[0]->DocumentNumber = $payLoad->Invoices[0]->DocumentNumber.'-REV';
                $payLoad->Invoices[0]->InvoiceDescription = $payLoad->Invoices[0]->InvoiceDescription.' - REVERSAL';
                $payLoad->Invoices[0]->DocumentType = 'CreditNote';
                $payLoad->Invoices[0]->ApplytoDocument = $applyToDocument;
                $sageRequestType = SageEnum::SRT_CREATE_AP_PREM_REV_INV;
            }

            if ($type == SageEnum::SCT_CORRECTION) {

                $payLoad->Invoices[0]->DocumentNumber = $payLoad->Invoices[0]->DocumentNumber.'-NEW';
                $payLoad->Invoices[0]->InvoiceDescription = $payLoad->Invoices[0]->InvoiceDescription.' - NEW';
                $payLoad->Invoices[0]->DocumentDate = Carbon::parse($request->insurerInvoiceDate)->format(self::instanceData()->sage_api_date_format);
                $payLoad->Invoices[0]->DueDate = Carbon::parse($request->paymentDueDate)->format(self::instanceData()->sage_api_date_format);
                $payLoad->Invoices[0]->DocumentTotalBeforeTaxes = roundNumber($request->premiumWithoutTax);
                $payLoad->Invoices[0]->DocumentTotalIncludingTax = roundNumber($request->premiumWithTax);
                $payLoad->Invoices[0]->PostingDate = Carbon::parse($request->bookingDate)->format(self::instanceData()->sage_api_date_format);
                $payLoad->Invoices[0]->DocumentType = 'DebitNote';
                $payLoad->Invoices[0]->ApplytoDocument = $applyToDocument;

                $payLoad->Invoices[0]->InvoicePaymentSchedules[0]->DueDate = Carbon::parse($request->paymentDueDate)->format(self::instanceData()->sage_api_date_format);
                $payLoad->Invoices[0]->InvoiceOptionalFields = self::createOptionalFields($request);

                $sageRequestType = SageEnum::SRT_CREATE_AP_PREM_CORR_INV;
            }
        }

        return [
            'endPoint' => 'AP/APInvoiceBatches',
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType,
            'entry_type' => $entryType,
        ];
    }

    public static function createARInvoiceDis($request, $type = SageEnum::SCT_STRAIGHT, $revCorrDetails = [])
    {
        // Payload creation logic for CreditNote scenario
        $description = 'D.'.$request->invoiceDescription;
        $payLoad = [
            'Invoices' => [
                [
                    'CustomerNumber' => $request->customerId,
                    'DocumentNumber' => $request->insurerPremiumNumber.'-DIS',
                    'InvoiceDescription' => $description,
                    'DocumentDate' => Carbon::parse($request->insurerInvoiceDate)->format(self::instanceData()->sage_api_date_format),
                    'DocumentType' => 'CreditNote',
                    'CurrencyCode' => 'AED',
                    'DueDate' => Carbon::parse($request->paymentDueDate)->format(self::instanceData()->sage_api_date_format),
                    'ApplytoDocument' => '',
                    'TaxGroup' => 'VAT',
                    'TaxClass1' => 5,
                    'DocumentTotalBeforeTax' => $request->discount,
                    'DocumentTotalIncludingTax' => $request->discount,
                    'PostingDate' => Carbon::parse($request->bookingDate)->format(self::instanceData()->sage_api_date_format),
                    'InvoiceDetails' => [
                        [
                            'Description' => $description,
                            'TaxClass1' => 5,
                            'RevenueAccount' => '70010',
                            'ExtendedAmountWithTIP' => roundNumber($request->discount),
                            'ExtendedAmountWithoutTIP' => roundNumber($request->discount),
                        ],
                    ],
                    'InvoicePaymentSchedules' => [
                        [
                            'DueDate' => Carbon::parse($request->paymentDueDate)->format(self::instanceData()->sage_api_date_format),
                        ],
                    ],
                    'InvoiceOptionalFields' => self::createOptionalFields($request),
                ],
            ],
        ];

        $sageRequestType = SageEnum::SRT_CREATE_AR_DISC_INV;
        $entryType = SageEnum::SCT_STRAIGHT;

        // Payload uppdate logic for reversal and correction scenario
        if (in_array($type, [SageEnum::SCT_REVERSAL, SageEnum::SCT_CORRECTION]) && ! empty($revCorrDetails)) {
            $entryType = $type;
            $payLoad = json_decode($revCorrDetails);

            $applyToDocument = $payLoad->Invoices[0]->DocumentNumber;
            unset($payLoad->BatchStatus);

            if ($type == SageEnum::SCT_REVERSAL) {

                $payLoad->Invoices[0]->DocumentNumber = $payLoad->Invoices[0]->DocumentNumber.'-REV';
                $payLoad->Invoices[0]->InvoiceDescription = $payLoad->Invoices[0]->InvoiceDescription.' - REVERSAL';
                $payLoad->Invoices[0]->DocumentType = 'DebitNote';
                $payLoad->Invoices[0]->ApplytoDocument = $applyToDocument;
                $sageRequestType = SageEnum::SRT_CREATE_AR_DISC_REV_INV;
            }

            if ($type == SageEnum::SCT_CORRECTION) {

                $payLoad->Invoices[0]->DocumentNumber = $payLoad->Invoices[0]->DocumentNumber.'-NEW';
                $payLoad->Invoices[0]->InvoiceDescription = $payLoad->Invoices[0]->InvoiceDescription.' - NEW';
                $payLoad->Invoices[0]->DocumentDate = Carbon::parse($request->insurerInvoiceDate)->format(self::instanceData()->sage_api_date_format); //
                $payLoad->Invoices[0]->DueDate = Carbon::parse($request->paymentDueDate)->format(self::instanceData()->sage_api_date_format); //
                $payLoad->Invoices[0]->DocumentTotalBeforeTaxes = roundNumber($request->premiumWithoutTax);
                $payLoad->Invoices[0]->DocumentTotalIncludingTax = roundNumber($request->premiumWithTax);
                $payLoad->Invoices[0]->PostingDate = Carbon::parse($request->bookingDate)->format(self::instanceData()->sage_api_date_format); //
                $payLoad->Invoices[0]->DocumentType = 'CreditNote';
                $payLoad->Invoices[0]->ApplytoDocument = $applyToDocument;

                $payLoad->Invoices[0]->InvoicePaymentSchedules[0]->DueDate = Carbon::parse($request->paymentDueDate)->format(self::instanceData()->sage_api_date_format); //
                $payLoad->Invoices[0]->InvoiceOptionalFields = self::createOptionalFields($request);

                $sageRequestType = SageEnum::SRT_CREATE_AR_DISC_CORR_INV;
            }
        }

        return [
            'endPoint' => 'AR/ARInvoiceBatches',
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType,
            'entry_type' => $entryType,
        ];
    }

    public static function createARInvoicePremAndComm($request, $type = SageEnum::SCT_STRAIGHT, $revCorrDetails = '')
    {
        // Payload creation logic for default scenario
        $taxClass = 2;
        if ($request->commissionIncludingVat > 0) {
            $taxClass = 1;
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
                    'DocumentDate' => Carbon::parse($request->insurerInvoiceDate)->format(self::instanceData()->sage_api_date_format),
                    'CurrencyCode' => 'AED',
                    'DueDate' => Carbon::parse($request->paymentDueDate)->format(self::instanceData()->sage_api_date_format),
                    'TaxGroup' => 'VAT',
                    'TaxClass1' => 5,
                    'TaxAmount1' => 0.000,
                    'DocumentTotalBeforeTax' => roundNumber($premiumWithDiscount),
                    'DocumentTotalIncludingTax' => roundNumber($premiumWithDiscount),
                    'PostingDate' => Carbon::parse($request->bookingDate)->format(self::instanceData()->sage_api_date_format),
                    'InvoiceDetails' => [
                        [
                            'Description' => $premiumDescription,
                            'TaxClass1' => 5,
                            'RevenueAccount' => $request->insurerGlLiaiblityAccount,
                            'ExtendedAmountWithTIP' => roundNumber($premiumWithDiscount),
                            'ExtendedAmountWithoutTIP' => roundNumber($premiumWithDiscount),
                        ],
                    ],
                    'InvoicePaymentSchedules' => [
                        [
                            'DueDate' => Carbon::parse($request->paymentDueDate)->format(self::instanceData()->sage_api_date_format),
                        ],
                    ],
                    'InvoiceOptionalFields' => self::createOptionalFields($request),
                ],
                [
                    'CustomerNumber' => $request->customerId,
                    'DocumentNumber' => $request->insurerCommissionNumber,
                    'InvoiceDescription' => $commissionDescription,
                    'DocumentDate' => Carbon::parse($request->insurerInvoiceDate)->format(self::instanceData()->sage_api_date_format),
                    'CurrencyCode' => 'AED',
                    'DueDate' => Carbon::parse($request->paymentDueDate)->format(self::instanceData()->sage_api_date_format),
                    'TaxGroup' => 'VAT',
                    'TaxClass1' => $taxClass,
                    'TaxAmount1' => roundNumber($request->vatOnCommission),
                    'DocumentTotalBeforeTax' => $request->commissionIncludingVat > 0 ? roundNumber($request->commissionIncludingVat) : roundNumber($request->commissionWithOutVat),
                    'DocumentTotalIncludingTax' => $request->commissionIncludingVat > 0 ? roundNumber($request->commissionIncludingVat) : roundNumber($request->commissionWithOutVat),
                    'PostingDate' => Carbon::parse($request->bookingDate)->format(self::instanceData()->sage_api_date_format),
                    'InvoiceDetails' => [
                        [
                            'Description' => $commissionDescription,
                            'TaxClass1' => $taxClass,
                            'TaxAmount1' => roundNumber($request->vatOnCommission),
                            'RevenueAccount' => '60010',
                            'ExtendedAmountWithTIP' => roundNumber($request->commissionIncludingVat > 0 ? $request->commissionIncludingVat : $request->commissionWithOutVat),
                            'ExtendedAmountWithoutTIP' => roundNumber($request->commissionIncludingVat > 0 ? $request->commissionIncludingVat : $request->commissionWithOutVat),
                        ],
                    ],
                    'InvoicePaymentSchedules' => [
                        [
                            'DueDate' => Carbon::parse($request->paymentDueDate)->format(self::instanceData()->sage_api_date_format),
                        ],
                    ],
                    'InvoiceOptionalFields' => self::createOptionalFields($request),
                ],
            ],
        ];

        $sageRequestType = SageEnum::SRT_CREATE_AR_PREM_COMM_INV;
        $entryType = SageEnum::SCT_STRAIGHT;

        // Payload uppdate logic for reversal and correction scenario
        if (in_array($type, [SageEnum::SCT_REVERSAL, SageEnum::SCT_CORRECTION]) && ! empty($revCorrDetails)) {
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
                $payLoad->Invoices[0]->DocumentDate = Carbon::parse($request->insurerInvoiceDate)->format(self::instanceData()->sage_api_date_format); //
                $payLoad->Invoices[0]->DueDate = Carbon::parse($request->paymentDueDate)->format(self::instanceData()->sage_api_date_format); //
                $payLoad->Invoices[0]->DocumentTotalBeforeTax = $request->premiumWithoutTax;
                $payLoad->Invoices[0]->DocumentTotalIncludingTax = $request->premiumWithTax;
                $payLoad->Invoices[0]->PostingDate = Carbon::parse($request->bookingDate)->format(self::instanceData()->sage_api_date_format); //
                $payLoad->Invoices[0]->DocumentType = 'DebitNote';
                $payLoad->Invoices[0]->ApplytoDocument = $applyToDocumentPrem;

                $payLoad->Invoices[0]->InvoiceDetails[0]->Description = $payLoad->Invoices[0]->InvoiceDescription;
                $payLoad->Invoices[0]->InvoiceDetails[0]->ExtendedAmountWithTIP = roundNumber($request->premiumWithTax);
                $payLoad->Invoices[0]->InvoiceDetails[0]->ExtendedAmountWithoutTIP = roundNumber($request->premiumWithoutTax);

                $payLoad->Invoices[0]->InvoicePaymentSchedules[0]->DueDate = Carbon::parse($request->paymentDueDate)->format(self::instanceData()->sage_api_date_format); //
                $payLoad->Invoices[0]->InvoiceOptionalFields = self::createOptionalFields($request);

                // Commision Invoice Correction
                $payLoad->Invoices[1]->DocumentNumber = $payLoad->Invoices[1]->DocumentNumber.'-NEW';
                $payLoad->Invoices[1]->InvoiceDescription = $payLoad->Invoices[1]->InvoiceDescription.' - NEW';
                $payLoad->Invoices[1]->DocumentDate = Carbon::parse($request->insurerInvoiceDate)->format(self::instanceData()->sage_api_date_format); //
                $payLoad->Invoices[1]->DueDate = Carbon::parse($request->paymentDueDate)->format(self::instanceData()->sage_api_date_format); //
                $payLoad->Invoices[1]->DocumentTotalBeforeTax = $request->commission;
                $payLoad->Invoices[1]->DocumentTotalIncludingTax = $request->commissionIncludingVat;
                $payLoad->Invoices[1]->PostingDate = Carbon::parse($request->bookingDate)->format(self::instanceData()->sage_api_date_format); //
                $payLoad->Invoices[1]->DocumentType = 'DebitNote';
                $payLoad->Invoices[1]->ApplytoDocument = $applyToDocumentComm;

                $payLoad->Invoices[1]->InvoiceDetails[0]->Description = $payLoad->Invoices[1]->InvoiceDescription;
                $payLoad->Invoices[1]->InvoiceDetails[0]->ExtendedAmountWithTIP = roundNumber($request->commissionIncludingVat);
                $payLoad->Invoices[1]->InvoiceDetails[0]->ExtendedAmountWithoutTIP = roundNumber($request->commission);

                $payLoad->Invoices[1]->InvoicePaymentSchedules[0]->DueDate = Carbon::parse($request->paymentDueDate)->format(self::instanceData()->sage_api_date_format); //
                $payLoad->Invoices[1]->InvoiceOptionalFields = self::createOptionalFields($request);

                $sageRequestType = SageEnum::SRT_CREATE_AR_PREM_COMM_CORR_INV;
            }
        }

        return [
            'endPoint' => 'AR/ARInvoiceBatches',
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType,
            'entry_type' => $entryType,
        ];
    }

    public static function createARInvoiceSplitPayments($request, $splitPayments)
    {
        // Payload creation logic for default scenario
        $taxClass = 2;
        if ($request->commissionIncludingVat > 0) {
            $taxClass = 1;
        }
        $entryType = SageEnum::SCT_STRAIGHT;
        $premiumDescription = 'P.'.$request->invoiceDescription;
        $commissionDescription = 'C.'.$request->invoiceDescription;
        $payLoad = [
            'Invoices' => [
                [
                    'CustomerNumber' => $request->customerId,
                    'DocumentNumber' => $request->insurerPremiumNumber,
                    'InvoiceDescription' => $premiumDescription,
                    'DocumentDate' => Carbon::parse($request->insurerInvoiceDate)->format(self::instanceData()->sage_api_date_format),
                    'CurrencyCode' => 'AED',
                    'DueDate' => $request->paymentDueDate ? Carbon::parse($request->paymentDueDate)->format(self::instanceData()->sage_api_date_format) : null,
                    'TaxGroup' => 'VAT',
                    'TaxClass1' => 5,
                    'TaxAmount1' => 0.000,
                    'DocumentTotalBeforeTax' => roundNumber($request->premiumWithTax),
                    'DocumentTotalIncludingTax' => roundNumber($request->premiumWithTax),
                    'PostingDate' => Carbon::parse($request->bookingDate)->format(self::instanceData()->sage_api_date_format),
                    'Terms' => 'SPLIT'.count($splitPayments),
                    'InvoiceDetails' => [
                        [
                            'Description' => $premiumDescription,
                            'TaxClass1' => 5,
                            'RevenueAccount' => $request->insurerGlLiaiblityAccount,
                            'ExtendedAmountWithTIP' => roundNumber($request->premiumWithTax),
                            'ExtendedAmountWithoutTIP' => roundNumber($request->premiumWithTax),
                        ],
                    ],

                    'InvoicePaymentSchedules' => self::createPaymentSchedules($splitPayments),
                    'InvoiceOptionalFields' => self::createOptionalFields($request),
                ],
                [
                    'CustomerNumber' => $request->customerId,
                    'DocumentNumber' => $request->insurerCommissionNumber,
                    'InvoiceDescription' => $commissionDescription,
                    'DocumentDate' => Carbon::parse($request->insurerInvoiceDate)->format(self::instanceData()->sage_api_date_format),
                    'CurrencyCode' => 'AED',
                    'DueDate' => $request->paymentDueDate ? Carbon::parse($request->paymentDueDate)->format(self::instanceData()->sage_api_date_format) : null,
                    'TaxGroup' => 'VAT',
                    'TaxClass1' => $taxClass,
                    'TaxAmount1' => roundNumber($request->vatOnCommission),
                    'DocumentTotalBeforeTax' => $request->commissionIncludingVat > 0 ? roundNumber($request->commissionIncludingVat) : roundNumber($request->commissionWithOutVat),
                    'DocumentTotalIncludingTax' => $request->commissionIncludingVat > 0 ? roundNumber($request->commissionIncludingVat) : roundNumber($request->commissionWithOutVat),
                    'PostingDate' => Carbon::parse($request->bookingDate)->format(self::instanceData()->sage_api_date_format),
                    'Terms' => 'SPLIT'.count($splitPayments),
                    'InvoiceDetails' => [
                        [
                            'Description' => $commissionDescription,
                            'TaxClass1' => $taxClass,
                            'TaxAmount1' => roundNumber($request->vatOnCommission),
                            'RevenueAccount' => '60010',
                            'ExtendedAmountWithTIP' => roundNumber($request->commissionIncludingVat > 0 ? $request->commissionIncludingVat : $request->commissionWithOutVat),
                            'ExtendedAmountWithoutTIP' => roundNumber($request->commissionIncludingVat > 0 ? $request->commissionIncludingVat : $request->commissionWithOutVat),
                        ],
                    ],
                    'InvoicePaymentSchedules' => [],
                    'InvoiceOptionalFields' => self::createOptionalFields($request),
                ],
            ],
        ];

        return [
            'endPoint' => 'AR/ARInvoiceBatches',
            'payload' => $payLoad,
            'sage_request_type' => SageEnum::SRT_CREATE_AR_SPPAY_INV,
            'entry_type' => $entryType,
        ];
    }

    public static function createPaymentSchedules($splitPayments)
    {
        $data = [];
        foreach ($splitPayments as $key => $item) {
            $temp['EntryNumber'] = 1;
            $temp['PaymentNumber'] = $key + 1;
            $temp['DueDate'] = date('Y-m-d', strtotime($item->due_date));
            $temp['AmountDue'] = roundNumber($item->collection_amount === null ? 0 : $item->collection_amount);
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
                    'BankReceiptAmount' => roundNumber(floatval($request->collection_amount)),
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
        $entryType = SageEnum::SCT_STRAIGHT;
        $payLoad = [
            'BatchStatus' => 'ReadyToPost',
        ];

        return [
            'endPoint' => 'AR/ARReceiptAndAdjustmentBatches'.'(BatchRecordType=\'CA\',BatchNumber='.$batchNumber.')',
            'payload' => $payLoad,
            'sage_request_type' => SageEnum::SRT_RTP_PAY_REC_ONE_INV,
            'entry_type' => $entryType,
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

    public static function readyToPostReceiptAr($batchNumber, $type = SageEnum::SCT_STRAIGHT, $useFor = SageEnum::SCT_STRAIGHT, $extras = [])
    {
        $sageRequestType = null;
        $payLoad = [
            'BatchStatus' => 'ReadyToPost',
        ];

        $sageRequestTypes = [
            SageEnum::SRT_CREATE_PAY_REC_ONE_INV => SageEnum::SRT_RTP_PAY_REC_ONE_INV,
            SageEnum::SRT_CREATE_AR_SP_PRE_PAYMENT => SageEnum::SRT_RTP_AR_SP_PRE_PAYMENT,
        ];

        if (isset($extras['sage_request_type'])) {
            $sageRequestType = $sageRequestTypes[$extras['sage_request_type']];
        }

        return [
            'endPoint' => 'AR/ARReceiptAndAdjustmentBatches'.'(BatchRecordType=\'CA\',BatchNumber='.$batchNumber.')',
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType ?? null,
            'entry_type' => SageEnum::SCT_STRAIGHT,
        ];
    }

    public static function aRPostReceipts($batchNumber, $type = SageEnum::SCT_STRAIGHT, $useFor = SageEnum::SCT_STRAIGHT, $extras = [])
    {
        $sageRequestType = null;
        $entryType = SageEnum::SCT_STRAIGHT;
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

        $sageRequestTypes = [
            SageEnum::SRT_CREATE_PAY_REC_ONE_INV => SageEnum::SRT_POST_PAY_REC_ONE_INV,
            SageEnum::SRT_CREATE_AR_SP_PRE_PAYMENT => SageEnum::SRT_POST_AR_SP_PRE_PAYMENT,
        ];

        if (isset($extras['sage_request_type'])) {
            $sageRequestType = $sageRequestTypes[$extras['sage_request_type']];
        }

        return [
            'endPoint' => 'AR/ARPostReceiptsAndAdjustments'.$val,
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType ?? null,
            'entry_type' => $entryType,
        ];
    }
    public static function readyToPostInvoiceAr($batchNumber, $type = SageEnum::SCT_STRAIGHT, $useFor = SageEnum::SCT_STRAIGHT, $extras = [])
    {
        $sageRequestType = null;
        $payLoad = [
            'BatchStatus' => 'ReadyToPost',
        ];

        $entryType = SageEnum::SCT_STRAIGHT;
        $sageRequestTypes = [
            SageEnum::SRT_CREATE_AR_PREM_COMM_INV => SageEnum::SRT_RTP_AR_PREM_COMM_INV,
            SageEnum::SRT_CREATE_AR_SPPAY_INV => SageEnum::SRT_RTP_AR_SPPAY_INV,
            SageEnum::SRT_CREATE_AR_DISC_INV => SageEnum::SRT_RTP_AR_DISC_INV,

        ];

        if (isset($extras['sage_request_type']) && ! in_array($type, [SageEnum::SCT_REVERSAL, SageEnum::SCT_CORRECTION])) {
            $sageRequestType = $sageRequestTypes[$extras['sage_request_type']];
        }

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
            'sage_request_type' => $sageRequestType ?? null,
            'entry_type' => $entryType,
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
            'entry_type' => $entryType,
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
            'entry_type' => $entryType,
        ];
    }

    public static function aRPostInvoices($batchNumber, $type = SageEnum::SCT_STRAIGHT, $useFor = SageEnum::SCT_STRAIGHT, $extras = [])
    {
        $sageRequestType = null;
        $payLoad = [
            'PostAllBatches' => 'Donotpostallbatches',
            'PostBatchFrom' => $batchNumber,
            'PostBatchTo' => $batchNumber,
            'ActionSelector' => 'string',
            'UpdateOperation' => 'Unspecified',
        ];

        $sign = '$process';
        $val = "('".$sign."')";

        $entryType = SageEnum::SCT_STRAIGHT;
        $sageRequestTypes = [
            SageEnum::SRT_CREATE_AR_PREM_COMM_INV => SageEnum::SRT_POST_AR_PREM_COMM_INV,
            SageEnum::SRT_CREATE_AR_SPPAY_INV => SageEnum::SRT_POST_AR_SPPAY_INV,
            SageEnum::SRT_CREATE_AR_DISC_INV => SageEnum::SRT_POST_AR_DISC_INV,
        ];

        if (isset($extras['sage_request_type']) && ! in_array($type, [SageEnum::SCT_REVERSAL, SageEnum::SCT_CORRECTION])) {
            $sageRequestType = $sageRequestTypes[$extras['sage_request_type']];
        }

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
            'sage_request_type' => $sageRequestType ?? null,
            'entry_type' => $entryType,
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
                'Value' => $request->policyBookingDate ?? null,
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
                'Value' => strval($request->vatOnPremium), // this variable initially defined as String, Sage Request break if it does not coverted to String
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

    public static function arSplitPrepaymentPayload($quote, $sage_customer_number, $payment, $splitPayments, $isPosAllSplitPayment = false)
    {
        $entryType = SageEnum::SCT_STRAIGHT;
        $payLoad = [
            'BatchRecordType' => 'CA',
            'ReceiptsAdjustments' => [
                [
                    'BatchType' => 'CA',
                    'CustomerNumber' => $sage_customer_number,
                    'ReceiptTransactionType' => 'Receipt',
                    'AppliedReceiptsAdjustments' => self::createAppliedReceiptsAdjustments($quote, $sage_customer_number, $payment, $splitPayments, $isPosAllSplitPayment),
                ],
            ],
        ];

        return [
            'endPoint' => 'AR/ARReceiptAndAdjustmentBatches',
            'payload' => $payLoad,
            'sage_request_type' => SageEnum::SRT_CREATE_AR_SP_PRE_PAYMENT,
            'entry_type' => $entryType,
        ];
    }

    private static function createReceiptData($item, $sage_customer_number, $payment, $paymentNumber = 1)
    {
        $receiptData = [
            'BatchType' => 'CA',
            'CustomerNumber' => $sage_customer_number,
            'DocumentNumber' => $payment->insurer_tax_number,
            'PaymentNumber' => $paymentNumber,
            'ReceiptTransactionType' => 'Receipt',
            'CustomerReceiptAmount' => roundNumber(floatval($item->payment_amount)),
        ];

        $prePaymentData = [
            'BatchType' => 'CA',
            'CustomerNumber' => $sage_customer_number,
            'DocumentNumber' => $item->sage_reciept_id,
            'PaymentNumber' => 1,
            'ReceiptTransactionType' => 'Receipt',
            'CustomerReceiptAmount' => -roundNumber($item->payment_amount),
        ];

        return [$receiptData, $prePaymentData];
    }

    public static function createAppliedReceiptsAdjustments($quote, $sageCustomerNumber, $paymentRecord, $splitPaymentRecords, $isPaymentsSplit)
    {
        $receiptsAndAdjustmentsData = [];

        if ($isPaymentsSplit) {
            foreach ($splitPaymentRecords as $index => $splitPaymentRecord) {
                [$singleReceiptData, $singlePrePaymentData] = self::createReceiptData($splitPaymentRecord, $sageCustomerNumber, $paymentRecord, $index + 1);
                $receiptsAndAdjustmentsData[] = $singleReceiptData;
                $receiptsAndAdjustmentsData[] = $singlePrePaymentData;
            }
        } else {
            $firstSplitPaymentRecord = $splitPaymentRecords[0];
            [$singleReceiptData, $singlePrePaymentData] = self::createReceiptData($firstSplitPaymentRecord, $sageCustomerNumber, $paymentRecord);
            $receiptsAndAdjustmentsData[] = $singleReceiptData;
            $receiptsAndAdjustmentsData[] = $singlePrePaymentData;
        }

        return $receiptsAndAdjustmentsData;
    }

    public static function sagePayLoad($quoteType, $quote, $payment, $splitPayments)
    {
        $quoteDetails = is_array($quote) ? $quote : $quote->toArray();
        $firstChildPayment = $splitPayments->first();
        $insuredFullName = isset($quote->customer_id) ? $quote?->customer?->insured_first_name.' '.$quote?->customer?->insured_last_name : '';

        $response = [
            'discount' => floatval($payment->discount_value),
            'invoiceDescription' => $payment->invoice_description,
            'bookingDate' => $quoteDetails['policy_booking_date'] ? date('Y-m-d', strtotime($quote['policy_booking_date'])) : null,
            'policyBookingDate' => $quoteDetails['policy_booking_date'] ? date('Ymd', strtotime($quoteDetails['policy_booking_date'])) : null,
            'policyExpiryDate' => date('Ymd', strtotime($quote['renewal_expiry_date'])),
            'insurerInvoiceDate' => date('Y-m-d', strtotime($payment->insurer_invoice_date)),
            'mainClassInsurance' => $quoteType,
            'policyNumber' => $quoteDetails['policy_number'],
            'policyIssuer' => $payment->policyIssuer?->name ?? '',
            'requestType' => Lookup::where('id', $quoteDetails['transaction_type_id'] ?? '')->first()->text ?? '',
            'subClass' => BusinessInsuranceType::where('id', $quoteDetails['business_type_of_insurance_id'] ?? '')->value('code') ?? '',
            'ccCode' => $firstChildPayment->cc_payment_id ?? '',
            'isPostDatedCheck' => ($firstChildPayment->payment_method == PaymentMethodsEnum::PostDatedCheque) ? 'Yes' : 'No',
            'checkDetails' => $firstChildPayment->check_detail ?? '',
            'endorsementNumber' => isset($quoteDetails['personal_quote_id']) ? SendUpdateLogRepository::endorsementsByPersonalQuoteId($quoteDetails['personal_quote_id'])->first()->code : '',
            'insured' => $insuredFullName,
            'policyHolder' => $insuredFullName,
            'premiumCollectedBy' => ucfirst($payment->collection_type),
            'invoicePaymentStatus' => $payment->transaction_payment_status,
            'advisorName' => ! empty($quoteDetails['advisor_id']) ? User::where('id', $quoteDetails['advisor_id'])->value('name') : '',
            'manager' => implode(',', getManagersByUser(User::where('id', ($quoteDetails['advisor_id'] ?? ''))->value('id'))->pluck('name')->toArray()),
            'vatOnPremium' => isset($quoteDetails['vat']) ?: (isset($quoteDetails['price_with_vat']) ? (floatval($quoteDetails['price_with_vat']) - floatval($quoteDetails['price_vat_applicable'] ?? 0)) : 0),
            'premiumWithoutTax' => floatval($quoteDetails['price_without_vat']),
            'premiumWithTax' => floatval($quoteDetails['price_with_vat']),
            'vatOnCommission' => floatval($payment->commission_vat),
            'totalAmount' => roundNumber(floatval($payment->total_amount)),
            'commission' => roundNumber(floatval($payment->commission)),
            'commissionIncludingVat' => roundNumber(floatval($payment->commission_vat_applicable)),
            'commissionWithOutVat' => $payment->commission_vat_not_applicable ? roundNumber(floatval($payment->commission_vat_not_applicable)) : roundNumber(floatval($payment->commission_without_vat)),
            'commissionPercentage' => strval($payment->commmission_percentage),
            'insurerPremiumNumber' => (string) $payment['insurer_tax_number'],
            'insurerCommissionNumber' => (string) $payment['insurer_commmission_invoice_number'],
            'insurerGlLiaiblityAccount' => $payment->insuranceProvider?->gl_liaiblity_account,
            'sageVenderId' => $payment->insuranceProvider?->sage_vendor_id,
        ];

        if (! empty($splitPayments)) {
            $response['paymentDueDate'] = date('Y-m-d', strtotime($splitPayments[0]['due_date']));
        }

        if (count($splitPayments) == 1) {
            $response['sage_reciept_id'] = $splitPayments[0]['sage_reciept_id'];
            $response['collection_amount'] = roundNumber($splitPayments[0]['collection_amount']); // + $sageRequest->discount
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
                        'aRPostInvoices',
                    ],
                    'extraDetails' => [
                        'createARInvoicePremAndComm' => [
                            'requestParms' => 'payload',
                            'nextCondition' => 'BatchNumber',
                            'errorMessage' => 'AR Invoice & prem failed from Sage',
                        ],
                        'readyToPostInvoiceAr' => [
                            'requestParms' => 'BatchNumber',
                            'verb' => 'PATCH',
                            'logResponse' => true,
                            'conditionChecks' => ['type' => 'Not Empty', 'condtion_to_check' => ''],
                            'errorMessage' => 'Error while making AR Invoice & prem ready to post to Sage',
                        ],
                        'aRPostInvoices' => [
                            'requestParms' => 'BatchNumber',
                            'logResponse' => true,
                            'conditionChecks' => ['type' => 'isset', 'condtion_to_check' => 'error'],
                            'errorMessage' => 'Error while making AR Invoice & prem Posted to Sage',
                        ],
                    ],
                ];
                break;

            case SageEnum::SRT_CREATE_AP_PREM_INV:
                $response = [
                    'steps' => 3,
                    'recursiveCalls' => [
                        'createAPInvoicePrem',
                        'readyToPostInvoiceAP',
                        'aPPostInvoices',
                    ],
                    'extraDetails' => [
                        'createAPInvoicePrem' => [
                            'requestParms' => 'payload',
                            'nextCondition' => 'BatchNumber',
                            'errorMessage' => 'AP Invoice prem failed from Sage',
                        ],
                        'readyToPostInvoiceAP' => [
                            'requestParms' => 'BatchNumber',
                            'verb' => 'PATCH',
                            'logResponse' => true,
                            'conditionChecks' => ['type' => 'Not Empty', 'condtion_to_check' => ''],
                            'errorMessage' => 'Error while making AP Invoice ready to post to Sage',
                        ],
                        'aPPostInvoices' => [
                            'requestParms' => 'BatchNumber',
                            'logResponse' => true,
                            'conditionChecks' => ['type' => 'isset', 'condtion_to_check' => 'error'],
                            'errorMessage' => 'Error while making AP Invoice posted to Sage',
                        ],
                    ],
                ];
                break;

            case SageEnum::SRT_CREATE_AR_DISC_INV:
                $response = [
                    'steps' => 3,
                    'recursiveCalls' => [
                        'createARInvoiceDis',
                        'readyToPostInvoiceAr',
                        'aRPostInvoices',
                    ],
                    'extraDetails' => [
                        'createARInvoiceDis' => [
                            'requestParms' => 'payload',
                            'nextCondition' => 'BatchNumber',
                            'errorMessage' => 'AR discount Invoice failed from Sage',
                        ],
                        'readyToPostInvoiceAr' => [
                            'requestParms' => 'BatchNumber',
                            'verb' => 'PATCH',
                            'logResponse' => true,
                            'conditionChecks' => ['type' => 'Not Empty', 'condtion_to_check' => ''],
                            'errorMessage' => 'Error while making AR discount Invoice ready to post to Sage',
                        ],
                        'aRPostInvoices' => [
                            'requestParms' => 'BatchNumber',
                            'logResponse' => true,
                            'conditionChecks' => ['type' => 'isset', 'condtion_to_check' => 'error'],
                            'errorMessage' => 'Error while making AR discount Invoice posted to Sage',
                        ],
                    ],
                ];
                break;

            case SageEnum::SRT_CREATE_AR_SPPAY_INV:
                $response = [
                    'recursiveCalls' => [
                        'readyToPostInvoiceAr',
                        'aRPostInvoices',
                    ],
                    'extraDetails' => [
                        'readyToPostInvoiceAr' => [
                            'requestParms' => 'BatchNumber',
                            'verb' => 'PATCH',
                            'logResponse' => true,
                            'conditionChecks' => ['type' => 'isset', 'condtion_to_check' => 'error'],
                            'errorMessage' => 'Error while making AR2 Apply Split payment ready to post to sage',
                        ],
                        'aRPostInvoices' => [
                            'requestParms' => 'BatchNumber',
                            'logResponse' => true,
                            'conditionChecks' => ['type' => 'isset', 'condtion_to_check' => 'error'],
                            'errorMessage' => 'Error while making AR2 Apply Split payment posted to sage',
                        ],
                    ],
                ];
                break;

            case SageEnum::SRT_CREATE_PAY_REC_ONE_INV:
                $response = [
                    'recursiveCalls' => [
                        'createPaymontRecieptOneInvoice',
                        'readyToPostReceiptAr',
                        'aRPostReceipts',
                    ],
                    'extraDetails' => [
                        'createPaymontRecieptOneInvoice' => [
                            'requestParms' => 'payload',
                            'nextCondition' => 'BatchNumber',
                            'errorMessage' => 'Error while making Split Pre-Payments to sage',
                        ],
                        'readyToPostReceiptAr' => [
                            'requestParms' => 'BatchNumber',
                            'verb' => 'PATCH',
                            'logResponse' => true,
                            'conditionChecks' => ['type' => 'Not Empty', 'condtion_to_check' => ''],
                            'errorMessage' => 'Error while making Apply payment ready to post to sage',
                        ],
                        'aRPostReceipts' => [
                            'requestParms' => 'BatchNumber',
                            'logResponse' => true,
                            'conditionChecks' => ['type' => 'isset', 'condtion_to_check' => 'error'],
                            'errorMessage' => 'Error while making Apply payment posted to sage',
                        ],
                    ],
                ];
                break;

            case SageEnum::SRT_CREATE_AR_SP_PRE_PAYMENT:
                $response = [
                    'recursiveCalls' => [
                        'arSplitPrepaymentPayload',
                        'readyToPostReceiptAr',
                        'aRPostReceipts',
                    ],
                    'extraDetails' => [
                        'arSplitPrepaymentPayload' => [
                            'requestParms' => 'payload',
                            'nextCondition' => 'BatchNumber',
                            'errorMessage' => 'Error while making apply Split Pre-Payments to sage',
                        ],
                        'readyToPostReceiptAr' => [
                            'requestParms' => 'BatchNumber',
                            'logResponse' => true,
                            'verb' => 'PATCH',
                            'conditionChecks' => ['type' => 'Not Empty', 'condtion_to_check' => ''],
                            'errorMessage' => 'Error while making Apply payment ready to post to sage',
                        ],
                        'aRPostReceipts' => [
                            'requestParms' => 'BatchNumber',
                            'logResponse' => true,
                            'conditionChecks' => ['type' => 'isset', 'condtion_to_check' => 'error'],
                            'errorMessage' => 'Error while making Apply payment Posted to sage',
                        ],
                    ],
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
                            'errorMessage' => $message[$apiType][$useFor]['createARInvoicePremAndComm']['error'],
                        ],
                        'readyToPostInvoiceAr' => [
                            'requestParms' => 'BatchNumber',
                            'verb' => 'PATCH',
                            'logResponse' => true,
                            'conditionChecks' => ['type' => 'Not Empty', 'condtion_to_check' => ''],
                            'errorMessage' => $message[$apiType][$useFor]['readyToPostInvoiceAr']['error'],
                        ],
                        'aRPostInvoices' => [
                            'requestParms' => 'BatchNumber',
                            'logResponse' => true,
                            'conditionChecks' => ['type' => 'isset', 'condtion_to_check' => 'error'],
                            'errorMessage' => $message[$apiType][$useFor]['aRPostInvoices']['error'],
                        ],
                    ],
                ];
                break;

            case SageEnum::SRT_REV_CORR_AP_PREM_INV:
                $response = [
                    'recursiveCalls' => [
                        'getInvoiceDetails',
                        'createAPInvoicePrem',
                        'readyToPostInvoiceAP',
                        'aPPostInvoices',
                    ],
                    'extraDetails' => [
                        'getInvoiceDetails' => [
                            'verb' => 'GET',
                        ],
                        'createAPInvoicePrem' => [
                            'requestParms' => 'payload',
                            'nextCondition' => 'BatchNumber',
                            'errorMessage' => $message[$apiType][$useFor]['createAPInvoicePrem']['error'],
                        ],
                        'readyToPostInvoiceAP' => [
                            'requestParms' => 'BatchNumber',
                            'verb' => 'PATCH',
                            'logResponse' => true,
                            'conditionChecks' => ['type' => 'Not Empty', 'condtion_to_check' => ''],
                            'errorMessage' => $message[$apiType][$useFor]['readyToPostInvoiceAP']['error'],
                        ],
                        'aPPostInvoices' => [
                            'requestParms' => 'BatchNumber',
                            'logResponse' => true,
                            'conditionChecks' => ['type' => 'isset', 'condtion_to_check' => 'error'],
                            'errorMessage' => $message[$apiType][$useFor]['aPPostInvoices']['error'],
                        ],
                    ],
                ];
                break;

            case SageEnum::SRT_REV_CORR_AR_DIS_INV:
                $response = [
                    'recursiveCalls' => [
                        'getInvoiceDetails',
                        'createARInvoiceDis',
                        'readyToPostInvoiceAr',
                        'aRPostInvoices',
                    ],
                    'extraDetails' => [
                        'getInvoiceDetails' => [
                            'verb' => 'GET',
                        ],
                        'createARInvoiceDis' => [
                            'requestParms' => 'payload',
                            'nextCondition' => 'BatchNumber',
                            'errorMessage' => $message[$apiType][$useFor]['createARInvoiceDis']['error'],
                        ],
                        'readyToPostInvoiceAr' => [
                            'requestParms' => 'BatchNumber',
                            'verb' => 'PATCH',
                            'logResponse' => true,
                            'conditionChecks' => ['type' => 'Not Empty', 'condtion_to_check' => ''],
                            'errorMessage' => $message[$apiType][$useFor]['readyToPostInvoiceAr']['error'],
                        ],
                        'aRPostInvoices' => [
                            'requestParms' => 'BatchNumber',
                            'logResponse' => true,
                            'conditionChecks' => ['type' => 'isset', 'condtion_to_check' => 'error'],
                            'errorMessage' => $message[$apiType][$useFor]['aRPostInvoices']['error'],
                        ],
                    ],
                ];
                break;
        }

        return $response;
    }

    public static function getErrorMessages()
    {
        $message = [
            SageEnum::SRT_REV_CORR_AR_PREM_COMM_INV => [
                SageEnum::SCT_STRAIGHT => [
                    'createARInvoicePremAndComm' => [
                        'error' => 'AR Invoice & prem failed from Sage',
                    ],
                    'readyToPostInvoiceAr' => [
                        'error' => 'Error while making AR Invoice & prem ready to post to Sage',
                    ],
                    'aRPostInvoices' => [
                        'error' => 'Error while making AR Invoice & prem Posted to Sage',
                    ],
                ],
                SageEnum::SCT_REVERSAL => [
                    'createARInvoicePremAndComm' => [
                        'error' => 'AR Invoice & prem reversal failed from Sage',
                    ],
                    'readyToPostInvoiceAr' => [
                        'error' => 'Error while making AR Invoice & prem reversal ready to post to Sage',
                    ],
                    'aRPostInvoices' => [
                        'error' => 'Error while making AR Invoice & prem reversal Posted to Sage',
                    ],
                ],
                SageEnum::SCT_CORRECTION => [
                    'createARInvoicePremAndComm' => [
                        'error' => 'AR Invoice & prem correction failed from Sage',
                    ],
                    'readyToPostInvoiceAr' => [
                        'error' => 'Error while making AR Invoice & prem correction ready to post to Sage',
                    ],
                    'aRPostInvoices' => [
                        'error' => 'Error while making AR Invoice & prem correction Posted to Sage',
                    ],
                ],
            ],
            SageEnum::SRT_REV_CORR_AP_PREM_INV => [
                SageEnum::SCT_STRAIGHT => [
                    'createAPInvoicePrem' => [
                        'error' => 'AP Invoice prem failed from Sage',
                    ],
                    'readyToPostInvoiceAP' => [
                        'error' => 'Error while making AP Invoice ready to post to Sage',
                    ],
                    'aPPostInvoices' => [
                        'error' => 'Error while making AP Invoice posted to Sage',
                    ],
                ],
                SageEnum::SCT_REVERSAL => [
                    'createAPInvoicePrem' => [
                        'error' => 'AP Invoice prem reversal failed from Sage',
                    ],
                    'readyToPostInvoiceAP' => [
                        'error' => 'Error while making AP Invoice reversal ready to post to Sage',
                    ],
                    'aPPostInvoices' => [
                        'error' => 'Error while making AP Invoice reversal posted to Sage',
                    ],
                ],
                SageEnum::SCT_CORRECTION => [
                    'createAPInvoicePrem' => [
                        'error' => 'AP Invoice prem correction failed from Sage',
                    ],
                    'readyToPostInvoiceAP' => [
                        'error' => 'Error while making AP Invoice correction ready to post to Sage',
                    ],
                    'aPPostInvoices' => [
                        'error' => 'Error while making AP Invoice correction posted to Sage',
                    ],
                ],
            ],
            SageEnum::SRT_REV_CORR_AR_DIS_INV => [
                SageEnum::SCT_STRAIGHT => [
                    'createARInvoiceDis' => [
                        'error' => 'AR discount Invoice failed from Sage',
                    ],
                    'readyToPostInvoiceAr' => [
                        'error' => 'Error while making AR discount Invoice ready to post to Sage',
                    ],
                    'aRPostInvoices' => [
                        'error' => 'Error while making AR discount Invoice posted to Sage',
                    ],
                ],
                SageEnum::SCT_REVERSAL => [
                    'createARInvoiceDis' => [
                        'error' => 'AR discount Invoice reversal failed from Sage',
                    ],
                    'readyToPostInvoiceAr' => [
                        'error' => 'Error while making AR discount Invoice reversal ready to post to Sage',
                    ],
                    'aRPostInvoices' => [
                        'error' => 'Error while making AR discount Invoice reversal posted to Sage',
                    ],
                ],
                SageEnum::SCT_CORRECTION => [
                    'createARInvoiceDis' => [
                        'error' => 'AR discount Invoice correction failed from Sage',
                    ],
                    'readyToPostInvoiceAr' => [
                        'error' => 'Error while making AR discount Invoice correction ready to post to Sage',
                    ],
                    'aRPostInvoices' => [
                        'error' => 'Error while making AR discount Invoice correction posted to Sage',
                    ],
                ],
            ],
        ];

        return $message;
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
