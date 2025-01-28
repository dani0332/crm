<?php

namespace App\Factories;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CollectionTypeEnum;
use App\Enums\PaymentFrequency;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\quoteStatusCode;
use App\Enums\SageEnum;
use App\Enums\SagePaymentMethodsEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\ApplicationStorage;
use App\Models\BusinessInsuranceType;
use App\Models\InsuranceProvider;
use App\Models\Lookup;
use App\Models\Payment;
use App\Models\PersonalQuote;
use App\Models\User;
use App\Repositories\SendUpdateLogRepository;
use Carbon\Carbon;
use stdClass;

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
        // This function might be outdated, it's not being used anywhere in the codebase
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
            return self::createPaymentReceiptOneInvoice($request); // Ignore this error because it's not being used anywhere in the codebase
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

    public static function createPaymentReceiptOneInvoice($quote, $sage_customer_number, $payment, $splitPayments, $isPosAllSplitPayment = false)
    {
        $entryType = SageEnum::SCT_STRAIGHT;
        $payLoad = [
            'BatchRecordType' => 'CA',
            'BankCode' => SageEnum::BANK_CODE,
            'ReceiptsAdjustments' => [
                [
                    'BatchType' => 'CA',
                    'CustomerNumber' => $sage_customer_number,
                    'BankCode' => SageEnum::BANK_CODE,
                    'ReceiptTransactionType' => 'Receipt',
                    'AppliedReceiptsAdjustments' => self::createAppliedReceiptsAdjustments($quote, $sage_customer_number, $payment, $splitPayments, $isPosAllSplitPayment),
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

    public static function createAPInvoicePrem($request, $type = SageEnum::SCT_STRAIGHT, $reversalDetails = '', $extras = [])
    {
        $optionalFields = self::createOptionalFields($request);
        // Additional Option Field just for AP Invoice
        $optionalFields[] = [
            'OptionalField' => 'IGTC',
            'Value' => 'N',
        ];
        $premiumDescription = 'P.'.$request->invoiceDescription;
        $invoicePaymentSchedulesDueDate = self::calculateDueDate($request->paymentDueDate, $request->insurerInvoiceDate);
        $payLoad = [
            'Invoices' => [
                [
                    'VendorNumber' => $request->sageVenderId, // use vender api to create vender in sage
                    'DocumentNumber' => $request->insurerPremiumNumber,
                    'InvoiceDescription' => $premiumDescription,
                    'DocumentDate' => Carbon::parse($request->insurerInvoiceDate)->format(self::instanceData()->sage_api_date_format), // Add date format because caught an error while calling sage for Send update
                    'CurrencyCode' => 'AED', // alway will be AED discussed with denber
                    'DueDate' => $invoicePaymentSchedulesDueDate,
                    'AsOfDate' => $invoicePaymentSchedulesDueDate,
                    'TaxGroup' => 'VAT', // alway will be VAT discussed with denber
                    'TaxClass1' => 5,
                    'TaxAmount1' => 0.000,
                    'DocumentTotalBeforeTaxes' => roundNumber($request->totalPrice),
                    'DocumentTotalIncludingTax' => roundNumber($request->totalPrice),
                    'PostingDate' => Carbon::parse($request->bookingDate)->format(self::instanceData()->sage_api_date_format), // Add date format because caught an error while calling sage for Send update
                    'InvoiceDetails' => [
                        [
                            'DistributionDescription' => $premiumDescription,
                            'TaxClass1' => 5,
                            'GLAccount' => $request->insurerGlLiaiblityAccount,
                            'DistributedAmount' => roundNumber($request->totalPrice),
                            'DistributedAmountBeforeTaxes' => roundNumber($request->totalPrice),
                        ],
                    ],
                    'InvoicePaymentSchedules' => [
                        [
                            'DueDate' => $invoicePaymentSchedulesDueDate,
                        ],
                    ],
                    'InvoiceOptionalFields' => $optionalFields,
                ],
            ],
        ];

        if (! empty($extras['mainLeadDetails']) && isset($extras['extras']['option_id']) && ! in_array($extras['extras']['option_id'], [
            SendUpdateLogStatusEnum::ACB,
            SendUpdateLogStatusEnum::ATIB,
        ])) {
            $payLoad['Invoices'][0]['DocumentType'] = 'CreditNote';
        }

        $sageRequestType = SageEnum::SRT_CREATE_AP_PREM_INV;
        $entryType = SageEnum::SCT_STRAIGHT;

        // Payload update logic for reversal and correction scenario
        if (in_array($type, [SageEnum::SCT_REVERSAL, SageEnum::SCT_CORRECTION]) && ! empty($reversalDetails)) {
            $entryType = $type;
            $reversePayLoad = json_decode($reversalDetails);

            unset($reversePayLoad->BatchStatus);
            unset($reversePayLoad->BatchNumber);

            if ($type == SageEnum::SCT_REVERSAL) {

                $reversePayLoad->Invoices[0]->DocumentNumber = $reversePayLoad->Invoices[0]->DocumentNumber.'-REV';
                $reversePayLoad->Invoices[0]->InvoiceDescription = $reversePayLoad->Invoices[0]->InvoiceDescription.' - REVERSAL';
                $reversePayLoad->Invoices[0]->DocumentType = 'CreditNote';
                $sageRequestType = SageEnum::SRT_CREATE_AP_PREM_REV_INV;
            }

            if ($type == SageEnum::SCT_CORRECTION) {
                $payLoad['Invoices'][0]['InvoiceDescription'] = $payLoad['Invoices'][0]['InvoiceDescription'].' - NEW';
                $sageRequestType = SageEnum::SRT_CREATE_AP_PREM_CORR_INV;
            }

            $payLoad = ($type == SageEnum::SCT_REVERSAL) ? $reversePayLoad : $payLoad;
        }

        return [
            'endPoint' => 'AP/APInvoiceBatches',
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType,
            'entry_type' => $entryType,
        ];
    }

    public static function createAPInvoiceSplitPayments($request, $paymentSplits, $type = SageEnum::SCT_STRAIGHT, $reversalDetails = '', $extras = [])
    {
        $optionalFields = self::createOptionalFields($request);
        // Additional Option Field just for AP Invoice
        $optionalFields[] = [
            'OptionalField' => 'IGTC',
            'Value' => 'N',
        ];
        $premiumDescription = 'P.'.$request->invoiceDescription;
        $invoicePaymentSchedulesDueDate = self::calculateDueDate($request->paymentDueDate, $request->insurerInvoiceDate);
        $payLoad = [
            'Invoices' => [
                [
                    'VendorNumber' => $request->sageVenderId, // use vender api to create vender in sage
                    'DocumentNumber' => $request->insurerPremiumNumber,
                    'InvoiceDescription' => $premiumDescription,
                    'DocumentDate' => Carbon::parse($request->insurerInvoiceDate)->format(self::instanceData()->sage_api_date_format), // Add date format because caught an error while calling sage for Send update
                    'CurrencyCode' => 'AED', // alway will be AED discussed with denber
                    'DueDate' => $invoicePaymentSchedulesDueDate,
                    'AsOfDate' => $invoicePaymentSchedulesDueDate,
                    'TaxGroup' => 'VAT', // alway will be VAT discussed with denber
                    'TaxClass1' => 5,
                    'TaxAmount1' => 0.000,
                    'DocumentTotalBeforeTaxes' => roundNumber($request->totalPrice),
                    'DocumentTotalIncludingTax' => roundNumber($request->totalPrice),
                    'Terms' => self::getTermsCode(count($paymentSplits)),
                    'PostingDate' => Carbon::parse($request->bookingDate)->format(self::instanceData()->sage_api_date_format), // Add date format because caught an error while calling sage for Send update
                    'InvoiceDetails' => [
                        [
                            'DistributionDescription' => $premiumDescription,
                            'TaxClass1' => 5,
                            'GLAccount' => $request->insurerGlLiaiblityAccount,
                            'DistributedAmount' => roundNumber($request->totalPrice),
                            'DistributedAmountBeforeTaxes' => roundNumber($request->totalPrice),
                        ],
                    ],
                    'InvoicePaymentSchedules' => self::createPaymentSchedules($paymentSplits, $invoicePaymentSchedulesDueDate),
                    'InvoiceOptionalFields' => $optionalFields,
                ],
            ],
        ];

        if (! empty($extras['mainLeadDetails']) && isset($extras['extras']['option_id']) && ! in_array($extras['extras']['option_id'], [
            SendUpdateLogStatusEnum::ACB,
            SendUpdateLogStatusEnum::ATIB,
        ])) {
            $payLoad['Invoices'][0]['DocumentType'] = 'CreditNote';
        }

        $sageRequestType = SageEnum::SRT_CREATE_AP_SPPAY_INV;
        $entryType = SageEnum::SCT_STRAIGHT;

        // Payload update logic for reversal and correction scenario
        if (in_array($type, [SageEnum::SCT_REVERSAL, SageEnum::SCT_CORRECTION]) && ! empty($reversalDetails)) {
            $entryType = $type;
            $reversepayLoad = json_decode($reversalDetails);

            unset($reversepayLoad->BatchStatus);
            unset($reversepayLoad->BatchNumber);

            if ($type == SageEnum::SCT_REVERSAL) {

                $reversepayLoad->Invoices[0]->DocumentNumber = $reversepayLoad->Invoices[0]->DocumentNumber.'-REV';
                $reversepayLoad->Invoices[0]->InvoiceDescription = $reversepayLoad->Invoices[0]->InvoiceDescription.' - REVERSAL';
                $reversepayLoad->Invoices[0]->DocumentType = 'CreditNote';
                $sageRequestType = SageEnum::SRT_CREATE_AP_SPPAY_REV_INV;
            }

            if ($type == SageEnum::SCT_CORRECTION) {
                $payLoad['Invoices'][0]['InvoiceDescription'] = $payLoad['Invoices'][0]['InvoiceDescription'].' - NEW';
                $payLoad['Invoices'][0]['InvoiceDetails'][0]['DistributionDescription'] = $payLoad['Invoices'][0]['InvoiceDescription'].' - NEW';
                $sageRequestType = SageEnum::SRT_CREATE_AP_SPPAY_CORR_INV;
            }

            $payLoad = ($type == SageEnum::SCT_REVERSAL) ? $reversepayLoad : $payLoad;
        }

        return [
            'endPoint' => 'AP/APInvoiceBatches',
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType,
            'entry_type' => $entryType,
        ];
    }

    public static function createARInvoiceDis($request, $type = SageEnum::SCT_STRAIGHT, $reversalDetails = '', $extras = [])
    {
        // Payload creation logic for CreditNote scenario
        $description = 'D.'.$request->invoiceDescription;
        $invoicePaymentSchedulesDueDate = self::calculateDueDate($request->paymentDueDate, $request->insurerInvoiceDate);
        $payLoad = [
            'Invoices' => [
                [
                    'CustomerNumber' => $request->customerId,
                    'DocumentNumber' => $request->insurerPremiumNumber.'-DIS',
                    'InvoiceDescription' => $description,
                    'DocumentDate' => Carbon::parse($request->insurerInvoiceDate)->format(self::instanceData()->sage_api_date_format),
                    'DocumentType' => 'CreditNote',
                    'CurrencyCode' => 'AED',
                    'DueDate' => $invoicePaymentSchedulesDueDate,
                    'AsOfDate' => $invoicePaymentSchedulesDueDate,
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
                            'DueDate' => $invoicePaymentSchedulesDueDate,
                        ],
                    ],
                    'InvoiceOptionalFields' => self::createOptionalFields($request),
                ],
            ],
        ];

        if (! empty($extras['mainLeadDetails'])) {
            $payLoad['Invoices'][0]['DocumentType'] = 'DebitNote';
        }

        $sageRequestType = SageEnum::SRT_CREATE_AR_DISC_INV;
        $entryType = SageEnum::SCT_STRAIGHT;

        // Payload update logic for reversal and correction scenario
        if (in_array($type, [SageEnum::SCT_REVERSAL, SageEnum::SCT_CORRECTION]) && ! empty($reversalDetails)) {
            $entryType = $type;
            $reversePayLoad = json_decode($reversalDetails);

            unset($reversePayLoad->BatchStatus);
            unset($reversePayLoad->BatchNumber);

            if ($type == SageEnum::SCT_REVERSAL) {

                $reversePayLoad->Invoices[0]->DocumentNumber = (string) mb_substr($reversePayLoad->Invoices[0]->DocumentNumber, -18).'-REV';
                $reversePayLoad->Invoices[0]->InvoiceDescription = $reversePayLoad->Invoices[0]->InvoiceDescription.' - REVERSAL';
                $reversePayLoad->Invoices[0]->DocumentType = 'DebitNote';
                $sageRequestType = SageEnum::SRT_CREATE_AR_DISC_REV_INV;
            }

            if ($type == SageEnum::SCT_CORRECTION) {
                $payLoad['Invoices'][0]['DocumentNumber'] = (string) mb_substr($payLoad['Invoices'][0]['DocumentNumber'], -18);
                $payLoad['Invoices'][0]['InvoiceDescription'] = $payLoad['Invoices'][0]['InvoiceDescription'].' - NEW';

                $sageRequestType = SageEnum::SRT_CREATE_AR_DISC_CORR_INV;
            }

            $payLoad = ($type == SageEnum::SCT_REVERSAL) ? $reversePayLoad : $payLoad;
        }

        return [
            'endPoint' => 'AR/ARInvoiceBatches',
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType,
            'entry_type' => $entryType,
        ];
    }

    public static function createARInvoicePremAndComm($request, $type = SageEnum::SCT_STRAIGHT, $reversalDetails = '', $extras = [])
    {
        // Payload creation logic for default scenario
        $taxClass = 2;
        if ($request->commissionIncludingVat > 0) { // commissionIncludingVat means commission_vat_applicable,
            $taxClass = 1;
        }
        $premiumDescription = 'P.'.$request->invoiceDescription;
        $commissionDescription = 'C.'.$request->invoiceDescription;
        $invoicePaymentSchedulesDueDate = self::calculateDueDate($request->paymentDueDate, $request->insurerInvoiceDate);
        $optionalFields = self::createOptionalFields($request, SageEnum::SRT_CREATE_AR_PREM_COMM_INV);

        $payLoad = [
            'Invoices' => [
                [
                    'CustomerNumber' => $request->customerId,
                    'DocumentNumber' => $request->insurerPremiumNumber,
                    'InvoiceDescription' => $premiumDescription,
                    'DocumentDate' => Carbon::parse($request->insurerInvoiceDate)->format(self::instanceData()->sage_api_date_format),
                    'CurrencyCode' => 'AED',
                    'DueDate' => $invoicePaymentSchedulesDueDate,
                    'AsOfDate' => $invoicePaymentSchedulesDueDate,
                    'TaxGroup' => 'VAT',
                    'TaxClass1' => 5,
                    'TaxAmount1' => 0.000,
                    'DocumentTotalBeforeTax' => roundNumber($request->premiumWithTax),
                    'DocumentTotalIncludingTax' => roundNumber($request->premiumWithTax),
                    'PostingDate' => Carbon::parse($request->bookingDate)->format(self::instanceData()->sage_api_date_format),
                    'InvoiceDetails' => [
                        [
                            'Description' => $premiumDescription,
                            'TaxClass1' => 5,
                            'RevenueAccount' => $request->insurerGlLiaiblityAccount,
                            'ExtendedAmountWithTIP' => roundNumber($request->premiumWithTax),
                            'ExtendedAmountWithoutTIP' => roundNumber($request->premiumWithTax),
                        ],
                    ],
                    'InvoicePaymentSchedules' => [
                        [
                            'DueDate' => $invoicePaymentSchedulesDueDate,
                        ],
                    ],
                    'InvoiceOptionalFields' => $optionalFields,
                ],
                [
                    'CustomerNumber' => $request->sageInsurerCustomerId,
                    'DocumentNumber' => $request->insurerCommissionNumber,
                    'InvoiceDescription' => $commissionDescription,
                    'DocumentDate' => Carbon::parse($request->insurerInvoiceDate)->format(self::instanceData()->sage_api_date_format),
                    'CurrencyCode' => 'AED',
                    'DueDate' => $invoicePaymentSchedulesDueDate,
                    'AsOfDate' => $invoicePaymentSchedulesDueDate,
                    'TaxGroup' => 'VAT',
                    'TaxClass1' => $taxClass,
                    'TaxAmount1' => roundNumber($request->vatOnCommission),
                    'DocumentTotalBeforeTax' => $request->commissionIncludingVat > 0 ? roundNumber($request->commissionIncludingVat) : roundNumber($request->commissionWithOutVat), // commissionIncludingVat means commission_vat_applicable,
                    'DocumentTotalIncludingTax' => $request->commissionIncludingVat > 0 ? roundNumber($request->commissionIncludingVat) : roundNumber($request->commissionWithOutVat), // / commissionIncludingVat means commission_vat_applicable,
                    'PostingDate' => Carbon::parse($request->bookingDate)->format(self::instanceData()->sage_api_date_format),
                    'InvoiceDetails' => [
                        [
                            'Description' => $commissionDescription,
                            'TaxClass1' => $taxClass,
                            'TaxAmount1' => roundNumber($request->vatOnCommission),
                            'RevenueAccount' => '60010',
                            'ExtendedAmountWithTIP' => roundNumber($request->commissionIncludingVat > 0 ? $request->commissionIncludingVat : $request->commissionWithOutVat), // commissionIncludingVat means commission_vat_applicable,
                            'ExtendedAmountWithoutTIP' => roundNumber($request->commissionIncludingVat > 0 ? $request->commissionIncludingVat : $request->commissionWithOutVat), // commissionIncludingVat means commission_vat_applicable,
                        ],
                    ],
                    'InvoicePaymentSchedules' => [
                        [
                            'DueDate' => $invoicePaymentSchedulesDueDate,
                        ],
                    ],
                    'InvoiceOptionalFields' => $optionalFields,
                ],
            ],
        ];

        if (! empty($extras['mainLeadDetails']) && isset($extras['extras']['option_id']) &&
            ! in_array($extras['extras']['option_id'], [
                SendUpdateLogStatusEnum::ACB,
                SendUpdateLogStatusEnum::ATIB,
            ])) {
            $payLoad['Invoices'][0]['DocumentType'] = 'CreditNote';
            $payLoad['Invoices'][1]['DocumentType'] = 'CreditNote';
        }

        $sageRequestType = SageEnum::SRT_CREATE_AR_PREM_COMM_INV;
        $entryType = SageEnum::SCT_STRAIGHT;

        // Payload update logic for reversal and correction scenario
        if (in_array($type, [SageEnum::SCT_REVERSAL, SageEnum::SCT_CORRECTION]) && ! empty($reversalDetails)) {
            $entryType = $type;
            $reversePayLoad = json_decode($reversalDetails);

            unset($reversePayLoad->BatchStatus);
            unset($reversePayLoad->BatchNumber);

            if ($type == SageEnum::SCT_REVERSAL) {

                $reversePayLoad->Invoices[0]->DocumentNumber = $reversePayLoad->Invoices[0]->DocumentNumber.'-REV';
                $reversePayLoad->Invoices[0]->InvoiceDescription = $reversePayLoad->Invoices[0]->InvoiceDescription.' - REVERSAL';
                $reversePayLoad->Invoices[0]->DocumentType = 'CreditNote';

                $reversePayLoad->Invoices[1]->DocumentNumber = $reversePayLoad->Invoices[1]->DocumentNumber.'-REV';
                $reversePayLoad->Invoices[1]->InvoiceDescription = $reversePayLoad->Invoices[1]->InvoiceDescription.' - REVERSAL';
                $reversePayLoad->Invoices[1]->DocumentType = 'CreditNote';
                $sageRequestType = SageEnum::SRT_CREATE_AR_PREM_COMM_REV_INV;
            }

            if ($type == SageEnum::SCT_CORRECTION) {

                $payLoad['Invoices'][0]['InvoiceDescription'] = $payLoad['Invoices'][0]['InvoiceDescription'].' - NEW';
                $payLoad['Invoices'][0]['InvoiceDetails'][0]['Description'] = $payLoad['Invoices'][0]['InvoiceDescription'];

                // Commision Invoice Correction
                $payLoad['Invoices'][1]['InvoiceDescription'] = $payLoad['Invoices'][1]['InvoiceDescription'].' - NEW';
                $payLoad['Invoices'][1]['InvoiceDetails'][0]['Description'] = $payLoad['Invoices'][1]['InvoiceDescription'];

                $sageRequestType = SageEnum::SRT_CREATE_AR_PREM_COMM_CORR_INV;
            }

            $payLoad = ($type == SageEnum::SCT_REVERSAL) ? $reversePayLoad : $payLoad;
        }

        // Additional commission and Tax invoice booking Case
        if (isset($extras['extras']['option_id']) && in_array($extras['extras']['option_id'], [
            SendUpdateLogStatusEnum::ACB,
            SendUpdateLogStatusEnum::ATIB,
            SendUpdateLogStatusEnum::ATCRNB,
            SendUpdateLogStatusEnum::ATCRNB_RBB,
        ])) {
            $payLoadInvoice = collect($payLoad['Invoices']);
            $payLoad['Invoices'] = in_array($extras['extras']['option_id'], [SendUpdateLogStatusEnum::ATIB, SendUpdateLogStatusEnum::ATCRNB]) ? $payLoadInvoice->forget(1)->toArray() : $payLoadInvoice->forget(0)->values()->toArray();
            $sageRequestType = in_array($extras['extras']['option_id'], [SendUpdateLogStatusEnum::ATIB, SendUpdateLogStatusEnum::ATCRNB]) ? SageEnum::SRT_CREATE_AR_PREM_INV : SageEnum::SRT_CREATE_AR_COMM_INV;
        }

        return [
            'endPoint' => 'AR/ARInvoiceBatches',
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType,
            'entry_type' => $entryType,
        ];
    }

    public static function createARInvoiceSplitPayments($request, $splitPayments, $type = SageEnum::SCT_STRAIGHT, $reversalDetails = '', $extras = [])
    {
        // Payload creation logic for default scenario
        $taxClass = 2;
        if ($request->commissionIncludingVat > 0) { // commissionIncludingVat means commission_vat_applicable,
            $taxClass = 1;
        }
        $entryType = SageEnum::SCT_STRAIGHT;
        $premiumDescription = 'P.'.$request->invoiceDescription;
        $commissionDescription = 'C.'.$request->invoiceDescription;
        $invoicePaymentSchedulesDueDate = self::calculateDueDate($request->paymentDueDate, $request->insurerInvoiceDate);
        $optionalFields = self::createOptionalFields($request, SageEnum::SRT_CREATE_AR_SPPAY_INV);

        $payLoad = [
            'Invoices' => [
                [
                    'CustomerNumber' => $request->customerId,
                    'DocumentNumber' => $request->insurerPremiumNumber,
                    'InvoiceDescription' => $premiumDescription,
                    'DocumentDate' => Carbon::parse($request->insurerInvoiceDate)->format(self::instanceData()->sage_api_date_format),
                    'CurrencyCode' => 'AED',
                    'DueDate' => $invoicePaymentSchedulesDueDate,
                    'AsOfDate' => $invoicePaymentSchedulesDueDate,
                    'TaxGroup' => 'VAT',
                    'TaxClass1' => 5,
                    'TaxAmount1' => 0.000,
                    'DocumentTotalBeforeTax' => roundNumber($request->premiumWithTax),
                    'DocumentTotalIncludingTax' => roundNumber($request->premiumWithTax),
                    'PostingDate' => Carbon::parse($request->bookingDate)->format(self::instanceData()->sage_api_date_format),
                    'Terms' => self::getTermsCode(count($splitPayments)),
                    'InvoiceDetails' => [
                        [
                            'Description' => $premiumDescription,
                            'TaxClass1' => 5,
                            'RevenueAccount' => $request->insurerGlLiaiblityAccount,
                            'ExtendedAmountWithTIP' => roundNumber($request->totalPrice),
                            'ExtendedAmountWithoutTIP' => roundNumber($request->totalPrice),
                        ],
                    ],

                    'InvoicePaymentSchedules' => self::createPaymentSchedules($splitPayments, $invoicePaymentSchedulesDueDate),
                    'InvoiceOptionalFields' => $optionalFields,
                ],
                [
                    'CustomerNumber' => $request->sageInsurerCustomerId,
                    'DocumentNumber' => $request->insurerCommissionNumber,
                    'InvoiceDescription' => $commissionDescription,
                    'DocumentDate' => Carbon::parse($request->insurerInvoiceDate)->format(self::instanceData()->sage_api_date_format),
                    'CurrencyCode' => 'AED',
                    'DueDate' => $invoicePaymentSchedulesDueDate,
                    'AsOfDate' => $invoicePaymentSchedulesDueDate,
                    'TaxGroup' => 'VAT',
                    'TaxClass1' => $taxClass,
                    'TaxAmount1' => roundNumber($request->vatOnCommission),
                    'DocumentTotalBeforeTax' => $request->commissionIncludingVat > 0 ? roundNumber($request->commissionIncludingVat) : roundNumber($request->commissionWithOutVat), // commissionIncludingVat means commission_vat_applicable,
                    'DocumentTotalIncludingTax' => $request->commissionIncludingVat > 0 ? roundNumber($request->commissionIncludingVat) : roundNumber($request->commissionWithOutVat), // commissionIncludingVat means commission_vat_applicable,
                    'PostingDate' => Carbon::parse($request->bookingDate)->format(self::instanceData()->sage_api_date_format),
                    'Terms' => self::getTermsCode(count($splitPayments)),
                    'InvoiceDetails' => [
                        [
                            'Description' => $commissionDescription,
                            'TaxClass1' => $taxClass,
                            'TaxAmount1' => roundNumber($request->vatOnCommission),
                            'RevenueAccount' => '60010',
                            'ExtendedAmountWithTIP' => roundNumber($request->commissionIncludingVat > 0 ? $request->commissionIncludingVat : $request->commissionWithOutVat), // commissionIncludingVat means commission_vat_applicable,
                            'ExtendedAmountWithoutTIP' => roundNumber($request->commissionIncludingVat > 0 ? $request->commissionIncludingVat : $request->commissionWithOutVat), // commissionIncludingVat means commission_vat_applicable,
                        ],
                    ],
                    'InvoicePaymentSchedules' => [],
                    'InvoiceOptionalFields' => $optionalFields,
                ],
            ],
        ];

        if (! empty($extras['mainLeadDetails']) && isset($extras['extras']['option_id']) &&
            ! in_array($extras['extras']['option_id'], [
                SendUpdateLogStatusEnum::ACB,
                SendUpdateLogStatusEnum::ATIB,
            ])) {
            $payLoad['Invoices'][0]['DocumentType'] = 'CreditNote';
            $payLoad['Invoices'][1]['DocumentType'] = 'CreditNote';
        }

        $sageRequestType = SageEnum::SRT_CREATE_AR_SPPAY_INV;
        $entryType = SageEnum::SCT_STRAIGHT;

        // Payload uppdate logic for reversal and correction scenario
        if (in_array($type, [SageEnum::SCT_REVERSAL, SageEnum::SCT_CORRECTION]) && ! empty($reversalDetails)) {
            $entryType = $type;
            $reversePayLoad = json_decode($reversalDetails);

            unset($reversePayLoad->BatchStatus);
            unset($reversePayLoad->BatchNumber);

            if ($type == SageEnum::SCT_REVERSAL) {

                $reversePayLoad->Invoices[0]->DocumentNumber = $reversePayLoad->Invoices[0]->DocumentNumber.'-REV';
                $reversePayLoad->Invoices[0]->InvoiceDescription = $reversePayLoad->Invoices[0]->InvoiceDescription.' - REVERSAL';
                $reversePayLoad->Invoices[0]->DocumentType = 'CreditNote';

                $reversePayLoad->Invoices[1]->DocumentNumber = $reversePayLoad->Invoices[1]->DocumentNumber.'-REV';
                $reversePayLoad->Invoices[1]->InvoiceDescription = $reversePayLoad->Invoices[1]->InvoiceDescription.' - REVERSAL';
                $reversePayLoad->Invoices[1]->DocumentType = 'CreditNote';
                $sageRequestType = SageEnum::SRT_CREATE_AR_SPPAY_REV_INV;

            }

            if ($type == SageEnum::SCT_CORRECTION) {

                $payLoad['Invoices'][0]['InvoiceDescription'] = $payLoad['Invoices'][0]['InvoiceDescription'].' - NEW';
                $payLoad['Invoices'][0]['InvoiceDetails'][0]['Description'] = $payLoad['Invoices'][0]['InvoiceDescription'];

                // Commision Invoice Correction
                $payLoad['Invoices'][1]['InvoiceDescription'] = $payLoad['Invoices'][1]['InvoiceDescription'].' - NEW';
                $payLoad['Invoices'][1]['InvoiceDetails'][0]['Description'] = $payLoad['Invoices'][1]['InvoiceDescription'];

                $sageRequestType = SageEnum::SRT_CREATE_AR_SPPAY_CORR_INV;
            }

            $payLoad = ($type == SageEnum::SCT_REVERSAL) ? $reversePayLoad : $payLoad;
        }

        return [
            'endPoint' => 'AR/ARInvoiceBatches',
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType,
            'entry_type' => $entryType,
        ];
    }

    public static function createPaymentSchedules($splitPayments, $insurerInvoiceDate)
    {
        $data = [];
        foreach ($splitPayments as $key => $item) {
            if (! isset($item->payment)) {
                $item->payment = Payment::where('code', $item->code)->first();
            }

            $payment = $item->payment;
            if ($payment->frequency == PaymentFrequency::SPLIT_PAYMENTS) {
                $dueDate = $insurerInvoiceDate;
            } else {
                $dueDate = date('Y-m-d', strtotime($item->due_date));
                if ($item->sr_no == 1) {
                    $dueDate = $insurerInvoiceDate;
                }
            }

            $temp['EntryNumber'] = 1;
            $temp['PaymentNumber'] = $key + 1;
            $temp['DueDate'] = $dueDate;
            $temp['AmountDue'] = roundNumber($item->payment_amount);
            $data[] = $temp;
        }

        return $data;
    }

    public static function createCustomerPayload($customer, $entity)
    {
        $entryType = SageEnum::SCT_STRAIGHT;
        if ($entity) {
            $payLoad = [
                'CustomerNumber' => 'C'.$customer->id,
                'CustomerName' => $entity?->company_name,
                'GroupCode' => 'PHC',
            ];
        } else {
            $payLoad = [
                'CustomerNumber' => 'P'.$customer->id,
                'CustomerName' => $customer->insured_first_name.' '.$customer->insured_last_name,
                'GroupCode' => 'PHI',
            ];
        }

        return [
            'endPoint' => SageEnum::END_POINT_AR_CUSTOMER,
            'payload' => $payLoad,
            'customerNumber' => $payLoad['CustomerNumber'],
            'sage_request_type' => SageEnum::SRT_CREATE_CUSTOMER,
            'entry_type' => $entryType,
        ];
    }

    public static function createPrepaymentPayload($request)
    {
        $entryType = SageEnum::SCT_STRAIGHT;
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

        if (in_array($request->sage_payment_code, [PaymentMethodsEnum::InsurerPayment, PaymentMethodsEnum::PostDatedCheque])) {
            $payLoad['BankCode'] = SageEnum::BANK_CODE;
            $payLoad['ReceiptsAdjustments'][0]['BankCode'] = SageEnum::BANK_CODE;
            $payLoad['ReceiptsAdjustments'][0]['PaymentCode'] = SageEnum::PAYMENT_CODE;
        }

        return [
            'endPoint' => 'AR/ARReceiptAndAdjustmentBatches',
            'payload' => $payLoad,
            'sage_request_type' => SageEnum::SRT_CREATE_PP_REC,
            'entry_type' => $entryType,
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

        return [
            'endPoint' => 'AR/ARPostReceiptsAndAdjustments'.$val,
            'payload' => $payLoad,
            'sage_request_type' => SageEnum::SRT_POST_PP_REC,
            'entry_type' => $entryType,
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

        // Additional commission and Tax invoice booking Case
        if (isset($extras['extras']['option_id']) && in_array($extras['extras']['option_id'], [
            SendUpdateLogStatusEnum::ACB,
            SendUpdateLogStatusEnum::ATIB,
            SendUpdateLogStatusEnum::ATCRNB,
            SendUpdateLogStatusEnum::ATCRNB_RBB,
        ])) {
            $sageRequestType = in_array($extras['extras']['option_id'], [SendUpdateLogStatusEnum::ATIB, SendUpdateLogStatusEnum::ATCRNB]) ? SageEnum::SRT_RTP_AR_PREM_INV : SageEnum::SRT_RTP_AR_COMM_INV;
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

        // Additional commission and Tax invoice booking Case
        if (isset($extras['extras']['option_id']) && in_array($extras['extras']['option_id'], [
            SendUpdateLogStatusEnum::ACB,
            SendUpdateLogStatusEnum::ATIB,
            SendUpdateLogStatusEnum::ATCRNB,
            SendUpdateLogStatusEnum::ATCRNB_RBB,
        ])) {
            $sageRequestType = in_array($extras['extras']['option_id'], [SendUpdateLogStatusEnum::ATIB, SendUpdateLogStatusEnum::ATCRNB]) ? SageEnum::SRT_POST_AR_PREM_INV : SageEnum::SRT_POST_AR_COMM_INV;
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

    private static function createOptionalFields($request, $forSpecificInvoices = null)
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
                'Value' => $request->originalPolicyNumber,
            ],
            [
                'OptionalField' => 'POLICYHOLDER',
                'Value' => $request->policyHolder,
            ],
            [
                'OptionalField' => 'POLICYISSUER',
                'Value' => strval($request->policyIssuer),
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
            [
                'OptionalField' => 'ORITAXNUM',
                'Value' => $request->originalInsurerPremiumNumber,
            ],
            [
                'OptionalField' => 'ORICOMTAXNUM',
                'Value' => $request->originalInsurerCommissionNumber,
            ],
        ];

        if (in_array($forSpecificInvoices, [SageEnum::SRT_CREATE_AR_PREM_COMM_INV, SageEnum::SRT_CREATE_AR_SPPAY_INV])) {
            $optionalArray[] = [
                'OptionalField' => 'COMAMOUNT',
                'Value' => $request->commissionIncludingVat > 0 ? (string) roundNumber($request->commissionIncludingVat) : (string) roundNumber($request->commissionWithOutVat), // commissionIncludingVat means commission_vat_applicable,
            ];

            $optionalArray[] = [
                'OptionalField' => 'TOTALCOMM',
                'Value' => (string) $request->commission,
            ];

            $optionalArray[] = [
                'OptionalField' => 'INSURER',
                'Value' => (string) $request->insurerName,
            ];

            $optionalArray[] = ['OptionalField' => 'REFID', 'Value' => (string) (($request->quoteCode ?? $request->quoteRefId) ?? '')];
            $optionalArray[] = ['OptionalField' => 'SUREFID', 'Value' => (string) $request->endorsementNumber];
            $optionalArray[] = ['OptionalField' => 'ENDORSUBTYPE', 'Value' => (string) $request->endorsementSubType];
            $optionalArray[] = ['OptionalField' => 'DEPARTMENT', 'Value' => (string) $request->advisorDepartment];
        }

        return $optionalArray;
    }

    public static function arSplitPrepaymentPayload($quote, $sage_customer_number, $payment, $splitPayments, $isPosAllSplitPayment = false)
    {
        $entryType = SageEnum::SCT_STRAIGHT;
        $payLoad = [
            'BatchRecordType' => 'CA',
            'BankCode' => SageEnum::BANK_CODE,
            'ReceiptsAdjustments' => [
                [
                    'BatchType' => 'CA',
                    'CustomerNumber' => $sage_customer_number,
                    'BankCode' => SageEnum::BANK_CODE,
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
        $documentNumber = mb_substr($payment->insurer_tax_number, -18);
        $receiptData = [
            'BatchType' => 'CA',
            'CustomerNumber' => $sage_customer_number,
            'DocumentNumber' => $documentNumber,
            'PaymentNumber' => $paymentNumber,
            'ReceiptTransactionType' => 'Receipt',
            'CustomerReceiptAmount' => roundNumber(floatval($item->payment_amount) + ($item->sr_no == 1 ? floatval($payment->discount_value) : 0)),
        ];

        $prePaymentData = [
            'BatchType' => 'CA',
            'CustomerNumber' => $sage_customer_number,
            'DocumentNumber' => $item->sage_reciept_id,
            'PaymentNumber' => 1,
            'ReceiptTransactionType' => 'Receipt',
            'CustomerReceiptAmount' => -roundNumber($item->payment_amount),
        ];

        $discountData = null;
        if ($payment->discount_value > 0 && $item->sr_no == 1) {
            $discountData = [
                'BatchType' => 'CA',
                'CustomerNumber' => $sage_customer_number,
                'DocumentNumber' => $documentNumber.'-DIS',
                'PaymentNumber' => 1,
                'ReceiptTransactionType' => 'Receipt',
                'CustomerReceiptAmount' => -roundNumber($payment->discount_value),
            ];
        }

        return [$receiptData, $prePaymentData, $discountData];
    }

    public static function createAppliedReceiptsAdjustments($quote, $sageCustomerNumber, $paymentRecord, $splitPaymentRecords, $isPaymentsSplit)
    {
        if ($isPaymentsSplit) {
            $receiptsAndAdjustmentsData = self::createAppliedReceiptsAdjustmentsForSplitPayments($splitPaymentRecords, $sageCustomerNumber, $paymentRecord);
        } else {
            $firstSplitPaymentRecord = $splitPaymentRecords[0];
            $receiptsAndAdjustmentsData = self::createAppliedReceiptsAdjustmentsForNonSplitPayments($firstSplitPaymentRecord, $sageCustomerNumber, $paymentRecord);
        }

        return $receiptsAndAdjustmentsData;
    }

    public static function sagePayLoad($modelType, $payment, $quote, $paymentSplits): object
    {
        $firstChildPayment = $paymentSplits->first();
        $insuredFullName = isset($quote->customer_id) ? $quote?->customer?->insured_first_name.' '.$quote?->customer?->insured_last_name : '';
        $latestEndorsementCode = '';
        $endorsementSubType = '';

        if (isset($quote->personal_quote_id) && $quote?->personal_quote_id) {
            $latestEndorsement = SendUpdateLogRepository::endorsementsByPersonalQuoteId($quote->personal_quote_id)->first();
            $latestEndorsementCode = $latestEndorsement?->code;

            if (! empty($latestEndorsement->option_id)) {
                $endorsementSubType = Lookup::find($latestEndorsement?->option_id)?->text ?? '';
            }
        }

        $businessTypeOfInsuranceCode = '';
        if (isset($quote->business_type_of_insurance_id) && $quote?->business_type_of_insurance_id) {
            $businessTypeOfInsurance = BusinessInsuranceType::find($quote->business_type_of_insurance_id);
            $businessTypeOfInsuranceCode = $businessTypeOfInsurance->code;
        }

        if ($quote?->insly_migrated) {
            $premiumCollectedBy = ucfirst(CollectionTypeEnum::BROKER);
            $policyIssuer = $quote->booking_filled_by;
        } else {
            $premiumCollectedBy = ucfirst($payment->collection_type);
            $policyIssuer = $payment->policyIssuer?->name ?? '';
        }

        $sageRequest = new stdClass;

        $sageRequest->quoteRefId = PersonalQuote::find($quote?->personal_quote_id)?->code ?? '';
        $sageRequest->userId = auth()->id();
        $sageRequest->discount = floatval($payment->discount_value);
        $sageRequest->invoiceDescription = $payment->invoice_description;
        //        TODO:: Need to check with Ali Array to Std
        $sageRequest->bookingDate = $quote?->policy_booking_date ? date(env('DATE_FORMAT_ONLY'), strtotime($quote?->policy_booking_date)) : Carbon::now()->format(env('DATE_FORMAT_ONLY'));
        $sageRequest->policyBookingDate = $quote?->policy_booking_date ? date(env('SAGE_300_CUSTOM_API_DATE_FORMAT'), strtotime($quote?->policy_booking_date)) : Carbon::now()->format(env('SAGE_300_CUSTOM_API_DATE_FORMAT'));
        $sageRequest->policyExpiryDate = date(env('SAGE_300_CUSTOM_API_DATE_FORMAT'), strtotime($quote?->policy_expiry_date));
        $sageRequest->insurerInvoiceDate = date(env('DATE_FORMAT_ONLY'), strtotime($payment->insurer_invoice_date));

        if (! empty($paymentSplits)) {
            $sageRequest->paymentDueDate = date(env('DATE_FORMAT_ONLY'), strtotime($firstChildPayment->due_date));
        }

        $sageRequest->mainClassInsurance = $modelType;
        $sageRequest->policyNumber = mb_substr($quote->policy_number, 60);
        $sageRequest->originalPolicyNumber = $quote->policy_number;
        $sageRequest->policyIssuer = $policyIssuer;
        $sageRequest->requestType = Lookup::where('id', $quote->transaction_type_id)->first()->text ?? '';
        $sageRequest->subClass = $businessTypeOfInsuranceCode;
        $sageRequest->ccCode = $firstChildPayment->cc_payment_id ?? '';
        $sageRequest->isPostDatedCheck = $firstChildPayment->payment_method == PaymentMethodsEnum::PostDatedCheque ? 'Yes' : 'No';
        $sageRequest->checkDetails = $firstChildPayment->check_detail ?? '';
        $sageRequest->endorsementNumber = $latestEndorsementCode;
        $sageRequest->endorsementSubType = $endorsementSubType;
        $sageRequest->insured = $insuredFullName;
        $sageRequest->policyHolder = $insuredFullName;
        $sageRequest->premiumCollectedBy = $premiumCollectedBy;

        $sageRequest->invoicePaymentStatus = $payment->payment_status_id;
        $advisorName = '';
        $advisorDepartment = '';
        $managerName = '';
        if (! empty($quote->advisor_id)) {
            $advisor = User::with('department')->where('id', $quote->advisor_id)->first();
            $advisorName = $advisor->name;
            $managerName = implode(',', getManagersByUser($advisor->id)->pluck('name')->toArray());
            $advisorDepartment = $advisor?->department?->name;
        }
        $sageRequest->advisorName = $advisorName;
        $sageRequest->manager = $managerName;
        $sageRequest->advisorDepartment = $advisorDepartment;

        // calculate vat
        $vatPercentage = ApplicationStorage::where('key_name', ApplicationStorageEnums::VAT_VALUE)->first()?->value;
        $sageRequest->vatOnPremium = $vatPercentage && $quote->price_vat_applicable ? (($quote->price_vat_applicable * $vatPercentage) / 100) : 0;
        //        $sageRequest->vatOnPremium = isset($quote->vat) ?: (isset($quote->price_with_vat) ? (floatval($quote->price_with_vat) - floatval($quote->price_vat_applicable ?? 0)) : 0); TODO:: This was added previous endorsement function, need to verify

        $sageRequest->premiumWithoutTax = floatval($quote->price_vat_applicable ?? 0) + floatval($quote->price_vat_not_applicable ?? 0);
        $sageRequest->premiumWithTax = floatval($quote->price_with_vat);
        $sageRequest->vatOnCommission = floatval($payment->commission_vat);
        $sageRequest->totalAmount = floatval($payment->total_amount);
        $sageRequest->totalPrice = floatval($payment->total_price);
        $sageRequest->commission = floatval($payment->commission);
        $sageRequest->commissionIncludingVat = floatval($payment->commission_vat_applicable);
        $sageRequest->commissionWithOutVat = $payment->commission_vat_not_applicable ? floatval($payment->commission_vat_not_applicable) : floatval($payment->commission_without_vat);
        $sageRequest->commissionPercentage = strval($payment->commmission_percentage);

        // Slice the last 18 characters from the string to avoid sage document number length issue and store the original values in optional fields
        $sageRequest->insurerPremiumNumber = (string) mb_substr($payment->insurer_tax_number, -18);
        $sageRequest->insurerCommissionNumber = (string) mb_substr($payment->insurer_commmission_invoice_number, -18);
        $sageRequest->originalInsurerPremiumNumber = (string) $payment->insurer_tax_number;
        $sageRequest->originalInsurerCommissionNumber = (string) $payment->insurer_commmission_invoice_number;

        if (count($paymentSplits) == 1) {
            $sageRequest->sage_reciept_id = $firstChildPayment->sage_reciept_id;
            $sageRequest->collection_amount = $firstChildPayment->collection_amount + $sageRequest->discount;
        } else {
            $sageRequest->invoicePaymentStatus = $firstChildPayment->payment_status_id;
        }

        $insuranceProvider = getInsuranceProvider($payment, $modelType, $quote);

        if ($quote?->insly_migrated && ! empty($payment->send_update_log_id)) {
            $insuranceProviderDetails = InsuranceProvider::where('id', $quote->insurance_provider_id)->first();
            $sageVenderId = $insuranceProviderDetails?->sage_vendor_id;
            $sageInsurerCustomerId = $insuranceProviderDetails?->sage_insurer_customer_id;
            $insurerGlLiaiblityAccount = $insuranceProviderDetails?->gl_liaiblity_account;

        } else {
            $sageVenderId = $insuranceProvider?->sage_vendor_id;
            $sageInsurerCustomerId = $insuranceProvider?->sage_insurer_customer_id;
            $insurerGlLiaiblityAccount = $insuranceProvider?->gl_liaiblity_account;
        }

        // Insurer GL Account and Vendor Number
        $sageRequest->insurerGlLiaiblityAccount = $insurerGlLiaiblityAccount;
        $sageRequest->sageVenderId = $sageVenderId;
        $sageRequest->sageInsurerCustomerId = $sageInsurerCustomerId;
        $sageRequest->insurerName = $insuranceProvider?->text;
        $sageRequest->insurerID = $insuranceProvider?->id;

        return $sageRequest;
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

    // Payment code mapping
    public static function calculateDueDate($paymentDueDate, $insurerInvoiceDate)
    {
        // if due date is older than Insurer invoice date than use insure invoice date
        $paymentDueDateCarbonObject = Carbon::parse($paymentDueDate)->startOfDay();
        $insurerInvoiceDateDateCarbon = Carbon::parse($insurerInvoiceDate)->startOfDay();
        if ($insurerInvoiceDateDateCarbon->gt($paymentDueDateCarbonObject)) {
            return $insurerInvoiceDateDateCarbon->format(self::instanceData()->sage_api_date_format);
        }

        return $paymentDueDateCarbonObject->format(self::instanceData()->sage_api_date_format);
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
            PaymentMethodsEnum::InsureNowPayLater => SagePaymentMethodsEnum::SAGE_INSURER_NOW_PAY_LATER,
        ];
        if (array_key_exists($paymentMethod, $sagePaymentCodeMappingArray)) {
            return $sagePaymentCodeMappingArray[$paymentMethod];
        } else {
            return SagePaymentMethodsEnum::SAGE_BANK_TRANSFER;
        }
    }

    private static function getTermsCode($splitPaymentsCount)
    {
        return $splitPaymentsCount === 1 ? 'COD' : ($splitPaymentsCount >= 10 ? 'SPLI'.$splitPaymentsCount : 'SPLIT'.$splitPaymentsCount);
    }

    private static function createAppliedReceiptsAdjustmentsForSplitPayments($splitPaymentRecords, $sageCustomerNumber, $paymentRecord)
    {
        $receiptsAndAdjustmentsData = [];
        foreach ($splitPaymentRecords as $index => $splitPaymentRecord) {
            if (self::isPaymentProcessed($splitPaymentRecord->payment_status_id)) {
                [$singleReceiptData, $singlePrePaymentData, $discountData] = self::createReceiptData($splitPaymentRecord, $sageCustomerNumber, $paymentRecord, $index + 1);
                $receiptsAndAdjustmentsData[] = $singleReceiptData;
                $receiptsAndAdjustmentsData[] = $singlePrePaymentData;
                if ($discountData) {
                    $receiptsAndAdjustmentsData[] = $discountData;
                }
            }

        }

        return $receiptsAndAdjustmentsData;
    }
    private static function createAppliedReceiptsAdjustmentsForNonSplitPayments($firstSplitPaymentRecord, $sageCustomerNumber, $paymentRecord)
    {
        $receiptsAndAdjustmentsData = [];
        if (self::isPaymentProcessed($firstSplitPaymentRecord->payment_status_id)) {
            [$singleReceiptData, $singlePrePaymentData , $discountData] = self::createReceiptData($firstSplitPaymentRecord, $sageCustomerNumber, $paymentRecord);
            $receiptsAndAdjustmentsData[] = $singleReceiptData;
            $receiptsAndAdjustmentsData[] = $singlePrePaymentData;
            if ($discountData) {
                $receiptsAndAdjustmentsData[] = $discountData;
            }
        }

        return $receiptsAndAdjustmentsData;
    }

    private static function isPaymentProcessed($paymentStatusId)
    {
        return in_array($paymentStatusId, [PaymentStatusEnum::PAID, PaymentStatusEnum::CAPTURED]);
    }
}
