<?php

namespace App\Factories;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CollectionTypeEnum;
use App\Enums\PaymentFrequency;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteTypes;
use App\Enums\SageEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\ApplicationStorage;
use App\Models\BusinessInsuranceType;
use App\Models\InsuranceProvider;
use App\Models\Lookup;
use App\Models\Payment;
use App\Models\PersonalQuote;
use App\Models\SendUpdateLog;
use App\Models\User;
use App\Services\SageApiService;
use Carbon\Carbon;
use stdClass;

class SagePayloadFactory
{
    public static function instanceData()
    {
        return (object) [
            'sage_api_date_format' => config('constants.SAGE_300_API_DATE_FORMAT'),
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
            'BankCode' => SageEnum::BANK_CODE_INS,
            'ReceiptsAdjustments' => [
                [
                    'BatchType' => 'CA',
                    'CustomerNumber' => $sage_customer_number,
                    'BankCode' => SageEnum::BANK_CODE_INS,
                    'ReceiptTransactionType' => 'Receipt',
                    'AppliedReceiptsAdjustments' => self::createAppliedReceiptsAdjustmentsForAR($quote, $sage_customer_number, $payment, $splitPayments, $isPosAllSplitPayment),
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
        $optionalFields[] = [
            'OptionalField' => 'IGTC',
            'Value' => 'N',
        ];
        $premiumDescription = 'P.'.$request->invoiceDescription;
        $bookingDate = Carbon::parse($request->bookingDate)->format(self::instanceData()->sage_api_date_format);
        $payLoad = [
            'Invoices' => [
                [
                    'VendorNumber' => $request->sageVenderId, // use vender api to create vender in sage
                    'DocumentNumber' => $request->insurerPremiumNumber,
                    'InvoiceDescription' => $premiumDescription,
                    'DocumentDate' => $bookingDate, // Add date format because caught an error while calling sage for Send update
                    'CurrencyCode' => 'AED', // alway will be AED discussed with denber
                    'DueDate' => $bookingDate,
                    'AsOfDate' => $bookingDate,
                    'TaxGroup' => 'VAT', // alway will be VAT discussed with denber
                    'TaxClass1' => 5,
                    'TaxAmount1' => 0.000,
                    'DocumentTotalBeforeTaxes' => roundNumber($request->premiumWithTax),
                    'DocumentTotalIncludingTax' => roundNumber($request->premiumWithTax),
                    'PostingDate' => $bookingDate, // Add date format because caught an error while calling sage for Send update
                    'InvoiceDetails' => [
                        [
                            'DistributionDescription' => $premiumDescription,
                            'TaxClass1' => 5,
                            'GLAccount' => $request->insurerGlLiaiblityAccount,
                            'DistributedAmount' => roundNumber($request->premiumWithTax),
                            'DistributedAmountBeforeTaxes' => roundNumber($request->premiumWithTax),
                        ],
                    ],
                    'InvoicePaymentSchedules' => [
                        [
                            'DueDate' => $bookingDate,
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

            if ($type == SageEnum::SCT_REVERSAL) {
                $reversePayLoad = self::prepareReversalPayload($reversalDetails);
                $reversePayLoad = self::applyReversalTransformations($reversePayLoad, $request, 0);
                $reversePayLoad->Invoices[0]->DocumentType = 'CreditNote';
                $sageRequestType = SageEnum::SRT_CREATE_AP_PREM_REV_INV;
                $payLoad = $reversePayLoad;
            }

            if ($type == SageEnum::SCT_CORRECTION) {
                $payLoad = self::applyCorrectionTransformations($payLoad, 0, false);
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

    public static function createAPInvoiceSplitPayments($request, $paymentSplits, $type = SageEnum::SCT_STRAIGHT, $reversalDetails = '', $extras = [])
    {
        $optionalFields = self::createOptionalFields($request);
        // Additional Option Field just for AP Invoice
        $optionalFields[] = [
            'OptionalField' => 'IGTC',
            'Value' => 'N',
        ];
        $premiumDescription = 'P.'.$request->invoiceDescription;
        $bookingDate = Carbon::parse($request->bookingDate)->format(self::instanceData()->sage_api_date_format);
        $payLoad = [
            'Invoices' => [
                [
                    'VendorNumber' => $request->sageVenderId, // use vender api to create vender in sage
                    'DocumentNumber' => $request->insurerPremiumNumber,
                    'InvoiceDescription' => $premiumDescription,
                    'DocumentDate' => $bookingDate, // Add date format because caught an error while calling sage for Send update
                    'CurrencyCode' => 'AED', // alway will be AED discussed with denber
                    'DueDate' => $bookingDate,
                    'AsOfDate' => $bookingDate,
                    'TaxGroup' => 'VAT', // alway will be VAT discussed with denber
                    'TaxClass1' => 5,
                    'TaxAmount1' => 0.000,
                    'DocumentTotalBeforeTaxes' => roundNumber($request->premiumWithTax),
                    'DocumentTotalIncludingTax' => roundNumber($request->premiumWithTax),
                    'Terms' => self::getTermsCode(count($paymentSplits)),
                    'PostingDate' => $bookingDate, // Add date format because caught an error while calling sage for Send update
                    'InvoiceDetails' => [
                        [
                            'DistributionDescription' => $premiumDescription,
                            'TaxClass1' => 5,
                            'GLAccount' => $request->insurerGlLiaiblityAccount,
                            'DistributedAmount' => roundNumber($request->premiumWithTax),
                            'DistributedAmountBeforeTaxes' => roundNumber($request->premiumWithTax),
                        ],
                    ],
                    'InvoicePaymentSchedules' => self::createPaymentSchedules($paymentSplits, $bookingDate),
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

            if ($type == SageEnum::SCT_REVERSAL) {
                $reversePayLoad = self::prepareReversalPayload($reversalDetails);
                $reversePayLoad = self::applyReversalTransformations($reversePayLoad, $request, 0);
                $reversePayLoad->Invoices[0]->DocumentType = 'CreditNote';
                $sageRequestType = SageEnum::SRT_CREATE_AP_SPPAY_REV_INV;
                $payLoad = $reversePayLoad;
            }

            if ($type == SageEnum::SCT_CORRECTION) {
                $payLoad = self::applyCorrectionTransformations($payLoad, 0, true);
                $sageRequestType = SageEnum::SRT_CREATE_AP_SPPAY_CORR_INV;
            }
        }

        return [
            'endPoint' => 'AP/APInvoiceBatches',
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType,
            'entry_type' => $entryType,
        ];
    }

    public static function createARInvoicePremAndComm($request, $type = SageEnum::SCT_STRAIGHT, $reversalDetails = '', $extras = [])
    {
        $taxClass = 2;
        if ($request->commissionIncludingVat > 0) { // commissionIncludingVat means commission_vat_applicable,
            $taxClass = 1;
        }
        $premiumDescription = 'P.'.$request->invoiceDescription;
        $commissionDescription = 'C.'.$request->invoiceDescription;
        $bookingDate = Carbon::parse($request->bookingDate)->format(self::instanceData()->sage_api_date_format);
        $optionalFields = self::createOptionalFields($request, SageEnum::SRT_CREATE_AR_PREM_COMM_INV);

        $payLoad = [
            'Invoices' => [
                [
                    'CustomerNumber' => $request->customerId,
                    'DocumentNumber' => $request->insurerPremiumNumber,
                    'InvoiceDescription' => $premiumDescription,
                    'DocumentDate' => $bookingDate,
                    'CurrencyCode' => 'AED',
                    'DueDate' => $bookingDate,
                    'AsOfDate' => $bookingDate,
                    'TaxGroup' => 'VAT',
                    'TaxClass1' => 5,
                    'TaxAmount1' => 0.000,
                    'DocumentTotalBeforeTax' => roundNumber($request->premiumWithTax),
                    'DocumentTotalIncludingTax' => roundNumber($request->premiumWithTax),
                    'PostingDate' => $bookingDate,
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
                            'DueDate' => $bookingDate,
                        ],
                    ],
                    'InvoiceOptionalFields' => $optionalFields,
                ],
                [
                    'CustomerNumber' => $request->sageInsurerCustomerId,
                    'DocumentNumber' => $request->insurerCommissionNumber,
                    'InvoiceDescription' => $commissionDescription,
                    'DocumentDate' => $bookingDate,
                    'CurrencyCode' => 'AED',
                    'DueDate' => $bookingDate,
                    'AsOfDate' => $bookingDate,
                    'TaxGroup' => 'VAT',
                    'TaxClass1' => $taxClass,
                    'TaxAmount1' => roundNumber($request->vatOnCommission),
                    'DocumentTotalBeforeTax' => $request->commissionIncludingVat > 0 ? roundNumber($request->commissionIncludingVat) : roundNumber($request->commissionWithOutVat), // commissionIncludingVat means commission_vat_applicable,
                    'DocumentTotalIncludingTax' => $request->commissionIncludingVat > 0 ? roundNumber($request->commissionIncludingVat) : roundNumber($request->commissionWithOutVat), // / commissionIncludingVat means commission_vat_applicable,
                    'PostingDate' => $bookingDate,
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
                            'DueDate' => $bookingDate,
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

            if ($type == SageEnum::SCT_REVERSAL) {
                $reversePayLoad = self::prepareReversalPayload($reversalDetails);
                $reversePayLoad = self::applyReversalTransformations($reversePayLoad, $request, 0);
                $reversePayLoad->Invoices[0]->DocumentType = 'CreditNote';

                $reversePayLoad = self::applyReversalTransformations($reversePayLoad, $request, 1);
                $reversePayLoad->Invoices[1]->DocumentType = 'CreditNote';

                $sageRequestType = SageEnum::SRT_CREATE_AR_PREM_COMM_REV_INV;
                $payLoad = $reversePayLoad;
            }

            if ($type == SageEnum::SCT_CORRECTION) {
                $payLoad = self::applyCorrectionTransformations($payLoad, 0, true);
                $payLoad = self::applyCorrectionTransformations($payLoad, 1, true);
                $sageRequestType = SageEnum::SRT_CREATE_AR_PREM_COMM_CORR_INV;
            }
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
        $premiumDescription = 'P.'.$request->invoiceDescription;
        $commissionDescription = 'C.'.$request->invoiceDescription;
        $bookingDate = Carbon::parse($request->bookingDate)->format(self::instanceData()->sage_api_date_format);
        $optionalFields = self::createOptionalFields($request, SageEnum::SRT_CREATE_AR_SPPAY_INV);

        $payLoad = [
            'Invoices' => [
                [
                    'CustomerNumber' => $request->customerId,
                    'DocumentNumber' => $request->insurerPremiumNumber,
                    'InvoiceDescription' => $premiumDescription,
                    'DocumentDate' => $bookingDate,
                    'CurrencyCode' => 'AED',
                    'DueDate' => $bookingDate,
                    'AsOfDate' => $bookingDate,
                    'TaxGroup' => 'VAT',
                    'TaxClass1' => 5,
                    'TaxAmount1' => 0.000,
                    'DocumentTotalBeforeTax' => roundNumber($request->premiumWithTax),
                    'DocumentTotalIncludingTax' => roundNumber($request->premiumWithTax),
                    'PostingDate' => $bookingDate,
                    'Terms' => self::getTermsCode(count($splitPayments)),
                    'InvoiceDetails' => [
                        [
                            'Description' => $premiumDescription,
                            'TaxClass1' => 5,
                            'RevenueAccount' => $request->insurerGlLiaiblityAccount,
                            'ExtendedAmountWithTIP' => roundNumber($request->premiumWithTax),
                            'ExtendedAmountWithoutTIP' => roundNumber($request->premiumWithTax),
                        ],
                    ],

                    'InvoicePaymentSchedules' => self::createPaymentSchedules($splitPayments, $bookingDate),
                    'InvoiceOptionalFields' => $optionalFields,
                ],
                [
                    'CustomerNumber' => $request->sageInsurerCustomerId,
                    'DocumentNumber' => $request->insurerCommissionNumber,
                    'InvoiceDescription' => $commissionDescription,
                    'DocumentDate' => $bookingDate,
                    'CurrencyCode' => 'AED',
                    'DueDate' => $bookingDate,
                    'AsOfDate' => $bookingDate,
                    'TaxGroup' => 'VAT',
                    'TaxClass1' => $taxClass,
                    'TaxAmount1' => roundNumber($request->vatOnCommission),
                    'DocumentTotalBeforeTax' => $request->commissionIncludingVat > 0 ? roundNumber($request->commissionIncludingVat) : roundNumber($request->commissionWithOutVat), // commissionIncludingVat means commission_vat_applicable,
                    'DocumentTotalIncludingTax' => $request->commissionIncludingVat > 0 ? roundNumber($request->commissionIncludingVat) : roundNumber($request->commissionWithOutVat), // commissionIncludingVat means commission_vat_applicable,
                    'PostingDate' => $bookingDate,
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

        // Payload update logic for reversal and correction scenario
        if (in_array($type, [SageEnum::SCT_REVERSAL, SageEnum::SCT_CORRECTION]) && ! empty($reversalDetails)) {
            $entryType = $type;

            if ($type == SageEnum::SCT_REVERSAL) {
                $reversePayLoad = self::prepareReversalPayload($reversalDetails);
                $reversePayLoad = self::applyReversalTransformations($reversePayLoad, $request, 0);
                $reversePayLoad->Invoices[0]->DocumentType = 'CreditNote';

                $reversePayLoad = self::applyReversalTransformations($reversePayLoad, $request, 1);
                $reversePayLoad->Invoices[1]->DocumentType = 'CreditNote';

                $sageRequestType = SageEnum::SRT_CREATE_AR_SPPAY_REV_INV;
                $payLoad = $reversePayLoad;
            }

            if ($type == SageEnum::SCT_CORRECTION) {
                $payLoad = self::applyCorrectionTransformations($payLoad, 0, true);
                $payLoad = self::applyCorrectionTransformations($payLoad, 1, true);
                $sageRequestType = SageEnum::SRT_CREATE_AR_SPPAY_CORR_INV;
            }
        }

        // Additional commission and Tax invoice booking Case
        if (isset($extras['extras']['option_id']) && $extras['extras']['option_id'] == SendUpdateLogStatusEnum::ATIB) {
            $payLoadInvoice = collect($payLoad['Invoices']);
            $payLoad['Invoices'] = $payLoadInvoice->forget(1)->toArray();
            $sageRequestType = SageEnum::SRT_CREATE_AR_SPPAY_PREM_INV;
        }

        return [
            'endPoint' => 'AR/ARInvoiceBatches',
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType,
            'entry_type' => $entryType,
        ];
    }

    public static function createPaymentSchedules($splitPayments, $bookingDate)
    {
        $data = [];
        foreach ($splitPayments as $key => $item) {
            if (! isset($item->payment)) {
                $item->payment = Payment::where('code', $item->code)->first();
            }

            $payment = $item->payment;
            if ($payment->frequency == PaymentFrequency::SPLIT_PAYMENTS) {
                $dueDate = $bookingDate;
            } else {
                if ($item->sr_no == 1) {
                    $dueDate = $bookingDate;
                } else {
                    $dueDate = app(SageApiService::class)->resolveInstallmentDueDateAgainstBookingDate($item->due_date, $bookingDate);
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
            // TODO:: Need to be updated when contact and insured person FR approved
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

    public static function createPrepaymentReceiptPayload($sageRequest, $isCommissionReceipt = false)
    {
        $optionalFields = self::createPrepaymentOptionalFields($sageRequest);
        $optionalFields[] = [
            'OptionalField' => 'INSURERRCTNO',
            'Value' => $sageRequest->insurerReceiptNumber ?? 'N/A',
        ];

        $entryType = SageEnum::SCT_STRAIGHT;

        $customerNumber = $sageRequest->sage_customer_number;
        $bankCode = SageEnum::BANK_CODE_INS;
        $paymentCode = SageEnum::PAYMENT_CODE_IP;
        $bankReceiptAmount = roundNumber(floatval($sageRequest->collection_amount));
        $checkReceiptNumber = $sageRequest->checkDetails;

        if (in_array($sageRequest->sage_payment_code, [PaymentMethodsEnum::InsurerPayment, PaymentMethodsEnum::PostDatedCheque])) {
            $bankCode = SageEnum::BANK_CODE_INS;
            $paymentCode = SageEnum::PAYMENT_CODE_IP;
        }

        if ($isCommissionReceipt) {
            $bankCode = SageEnum::BANK_CODE_TAP;
            $customerNumber = $sageRequest->sageInsurerCustomerId;
            $paymentCode = SageEnum::PAYMENT_CODE_CREDIT_CARD;
            $bankReceiptAmount = $sageRequest->commission;
            $checkReceiptNumber = $sageRequest->commissionChargeId;
        }

        $payLoad = [
            'BatchRecordType' => 'CA',
            'BankCode' => $bankCode,
            'ReceiptsAdjustments' => [
                [
                    'BatchType' => 'CA',
                    'CustomerNumber' => $customerNumber,
                    'BankCode' => $bankCode,
                    'BankReceiptAmount' => $bankReceiptAmount,
                    'CheckReceiptNumber' => $checkReceiptNumber,
                    'PaymentCode' => $paymentCode,
                    'ReceiptTransactionType' => 'Prepayment',
                    'AppliedReceiptsAdjustments' => [
                        [
                            'BatchType' => 'CA',
                            'CustomerNumber' => $customerNumber,
                            'ReceiptTransactionType' => 'Prepayment',
                        ],
                    ],
                    'ReceiptAdjustmentOptionalField' => $optionalFields,
                ],
            ],
        ];

        return [
            'endPoint' => 'AR/ARReceiptAndAdjustmentBatches',
            'payload' => $payLoad,
            'sage_request_type' => $isCommissionReceipt ? SageEnum::CREATE_AR_COM_PP_REC : SageEnum::SRT_CREATE_PP_REC,
            'entry_type' => $entryType,
        ];
    }

    public static function readyToPostReceiptArPayment($batchNumber, $isCommissionReceipt = false)
    {
        $entryType = SageEnum::SCT_STRAIGHT;
        $payLoad = [
            'BatchStatus' => 'ReadyToPost',
        ];

        return [
            'endPoint' => 'AR/ARReceiptAndAdjustmentBatches'.'(BatchRecordType=\'CA\',BatchNumber='.$batchNumber.')',
            'payload' => $payLoad,
            'sage_request_type' => $isCommissionReceipt ? SageEnum::RTP_AR_COM_PP_REC : SageEnum::SRT_RTP_PAY_REC_ONE_INV,
            'entry_type' => $entryType,
        ];
    }
    public static function aRPostReceiptsPayment($batchNumber, $isCommissionReceipt = false)
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
            'sage_request_type' => $isCommissionReceipt ? SageEnum::POST_AR_COM_PP_REC : SageEnum::SRT_POST_PP_REC,
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

    public static function readyToPostInvoiceAr($batchNumber, $type = SageEnum::SCT_STRAIGHT, $extras = [])
    {
        $sageRequestType = null;
        $payLoad = [
            'BatchStatus' => 'ReadyToPost',
        ];

        $entryType = SageEnum::SCT_STRAIGHT;
        $sageRequestTypes = [
            SageEnum::SRT_CREATE_AR_PREM_COMM_INV => SageEnum::SRT_RTP_AR_PREM_COMM_INV,
            SageEnum::SRT_CREATE_AR_SPPAY_INV => SageEnum::SRT_RTP_AR_SPPAY_INV,
        ];

        if (isset($extras['sage_request_type']) && ! in_array($type, [SageEnum::SCT_REVERSAL, SageEnum::SCT_CORRECTION])) {
            $sageRequestType = $sageRequestTypes[$extras['sage_request_type']];
        }

        if (in_array($type, [SageEnum::SCT_REVERSAL, SageEnum::SCT_CORRECTION])) {
            $entryType = $type;
            $sageRequestType = ($type == SageEnum::SCT_REVERSAL) ? SageEnum::SRT_RTP_AR_PREM_COMM_REV_INV : SageEnum::SRT_RTP_AR_PREM_COMM_CORR_INV;
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

    public static function aRPostInvoices($batchNumber, $type = SageEnum::SCT_STRAIGHT, $extras = [])
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
        ];

        if (isset($extras['sage_request_type']) && ! in_array($type, [SageEnum::SCT_REVERSAL, SageEnum::SCT_CORRECTION])) {
            $sageRequestType = $sageRequestTypes[$extras['sage_request_type']];
        }

        if (in_array($type, [SageEnum::SCT_REVERSAL, SageEnum::SCT_CORRECTION])) {
            $entryType = $type;
            $sageRequestType = ($type == SageEnum::SCT_REVERSAL) ? SageEnum::SRT_POST_AR_PREM_COMM_REV_INV : SageEnum::SRT_POST_AR_PREM_COMM_CORR_INV;
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

    private static function createPrepaymentOptionalFields($sageRequest)
    {
        $optionalArray = [
            [
                'OptionalField' => 'CHEQUENO',
                'Value' => $sageRequest->checkNumber ?? 'N/A',
            ],
            [
                'OptionalField' => 'DEPARTMENT',
                'Value' => $sageRequest->advisorDepartment,
            ],
            [
                'OptionalField' => 'INSURER',
                'Value' => $sageRequest->insurerName,
            ],
            [
                'OptionalField' => 'LINEOFBUSNSS',
                'Value' => $sageRequest->mainClassInsurance,
            ],
            [
                'OptionalField' => 'ORICOMTAXNUM',
                'Value' => $sageRequest->originalCommissionTaxInvoiceNumber,
            ],
            [
                'OptionalField' => 'PAYMENTGTWAY',
                'Value' => $sageRequest->paymentGateway ?? 'N/A',
            ],
            [
                'OptionalField' => 'PAYMENTMETHD',
                'Value' => $sageRequest->paymentMethod,
            ],
            [
                'OptionalField' => 'POLICY',
                'Value' => $sageRequest->policyNumber,
            ],
            [
                'OptionalField' => 'POLICYBKNGDT',
                'Value' => $sageRequest->bookingDate,
            ],
            [
                'OptionalField' => 'REFID',
                'Value' => $sageRequest->quoteCode,
            ],
            [
                'OptionalField' => 'SUREFID',
                'Value' => $sageRequest->endorsementNumber ?? 'N/A',
            ],
            [
                'OptionalField' => 'ENDORSEMENT',
                'Value' => $sageRequest->sendUpdateEndorsementNumber ?? 'N/A',
            ],
            [
                'OptionalField' => 'INSTAXINVAMT',
                'Value' => (string) $sageRequest->premiumWithTax,
            ],
            [
                'OptionalField' => 'INSTAXINVNO',
                'Value' => $sageRequest->originalInsurerPremiumNumber,
            ],
        ];

        return $optionalArray;
    }

    public static function arSplitPrepaymentPayload($quote, $sage_customer_number, $payment, $splitPayments, $isPosAllSplitPayment = false)
    {
        $entryType = SageEnum::SCT_STRAIGHT;
        $payLoad = [
            'BatchRecordType' => 'CA',
            'BankCode' => SageEnum::BANK_CODE_INS,
            'ReceiptsAdjustments' => [
                [
                    'BatchType' => 'CA',
                    'CustomerNumber' => $sage_customer_number,
                    'BankCode' => SageEnum::BANK_CODE_INS,
                    'ReceiptTransactionType' => 'Receipt',
                    'AppliedReceiptsAdjustments' => self::createAppliedReceiptsAdjustmentsForAR($quote, $sage_customer_number, $payment, $splitPayments, $isPosAllSplitPayment),
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

    private static function createReceiptDataForAR($item, $sage_customer_number, $payment, $paymentNumber = 1)
    {
        $documentNumber = mb_substr($payment->insurer_tax_number, -18);
        $receiptData = [
            'BatchType' => 'CA',
            'CustomerNumber' => $sage_customer_number,
            'DocumentNumber' => $documentNumber,
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

    public static function createAppliedReceiptsAdjustmentsForAR($quote, $sageCustomerNumber, $paymentRecord, $splitPaymentRecords, $isPaymentsSplit)
    {
        if ($isPaymentsSplit) {
            $receiptsAndAdjustmentsData = self::createAppliedReceiptsAdjustmentsForSplitPaymentsForAR($splitPaymentRecords, $sageCustomerNumber, $paymentRecord);
        } else {
            $firstSplitPaymentRecord = $splitPaymentRecords[0];
            $receiptsAndAdjustmentsData = self::createAppliedReceiptsAdjustmentsForNonSplitPaymentsForAR($firstSplitPaymentRecord, $sageCustomerNumber, $paymentRecord);
        }

        return $receiptsAndAdjustmentsData;
    }

    public static function globalSagePrepaymentReceiptPayloadData($sageRequestData)
    {
        [$quote, $payment, $paymentSplit , $sageRequest , $splitAmount] = $sageRequestData;
        $personalQuote = PersonalQuote::find($quote?->personal_quote_id);
        $policyNumber = $quote?->policy_number ?? $personalQuote?->policy_number ?? '';
        $sageRequest->collection_amount = $paymentSplit->collection_amount;
        if ($splitAmount != null) {
            $sageRequest->collection_amount = $splitAmount;
        }

        if (! isset($sageRequest->quoteTypeId)) {
            $sageRequest->quoteTypeId = QuoteTypes::getIdFromValue($sageRequest->quoteType);
        }

        if (! isset($sageRequest->advisor_id)) {
            $sageRequest->advisor_id = $quote->advisor_id;
        }
        if (! isset($sageRequest->customer_id)) {
            $sageRequest->customer_id = $quote->customer_id;
        }

        if (! isset($sageRequest->advisorDepartment)) {
            $advisorDepartment = '';
            if (! empty($quote->advisor_id)) {
                $advisor = User::with('department')->where('id', $quote->advisor_id)->first();
                $advisorDepartment = $advisor?->department?->name;
            }

            $sageRequest->advisorDepartment = $advisorDepartment;
        }
        if (! isset($sageRequest->mainClassInsurance)) {
            $sageRequest->mainClassInsurance = $sageRequest->quoteType;
        }
        $sageRequest->quoteCode = ! empty($sageRequest->quoteRefId) ? $sageRequest->quoteRefId : ($personalQuote?->code ?? $quote?->code);

        $insuranceProvider = (isset($sageRequest->insurerID) && $sageRequest->insurerID)
            ? InsuranceProvider::find($sageRequest->insurerID)
            : getInsuranceProvider($payment, $sageRequest->quoteType, $quote);

        $sageRequest->sageVenderId = $insuranceProvider?->sage_vendor_id;
        $sageRequest->insurerName = $insuranceProvider?->text;
        $sageRequest->insurerID = $insuranceProvider?->id;
        $sageRequest->insurerCode = $insuranceProvider?->code;
        $sageRequest->insurerPaymentGatewayId = $insuranceProvider?->payment_gateway_id;
        $sageRequest->sageInsurerCustomerId = $insuranceProvider?->sage_insurer_customer_id;
        $sageRequest->sage_payment_code = $paymentSplit->payment_method;
        $sageRequest->checkNumber = $paymentSplit->check_detail;
        $sageRequest->checkDetails = $paymentSplit->check_detail;
        $sageRequest->originalCommissionTaxInvoiceNumber = $payment?->insurer_commmission_invoice_number;
        $sageRequest->paymentGateway = $paymentSplit?->cc_payment_gateway;
        $sageRequest->paymentMethod = $paymentSplit?->payment_method;

        if (! isset($sageRequest->endorsementNumber)) {
            $sendUpdateLog = $payment?->sendUpdateLog;
            $sageRequest->endorsementNumber = $sendUpdateLog?->code;
            $sageRequest->sendUpdateEndorsementNumber = $sendUpdateLog?->endorsement_number;
        }

        if (! isset($sageRequest->insurerReceiptNumber)) {
            $sageRequest->insurerReceiptNumber = $paymentSplit?->insurer_receipt_number ?? null;
        }
        if (! isset($sageRequest->premiumWithTax)) {
            $sageRequest->premiumWithTax = floatval($quote->price_with_vat);
        }
        if (! isset($sageRequest->insurerPremiumNumber)) {
            $sageRequest->insurerPremiumNumber = (string) mb_substr($payment->insurer_tax_number, -18);
            $sageRequest->originalInsurerPremiumNumber = (string) $payment->insurer_tax_number;
        }

        $sageRequest->policyNumber = $policyNumber;
        $sageRequest->bookingDate = $quote?->policy_booking_date ? date(config('constants.DATE_FORMAT_ONLY'), strtotime($quote?->policy_booking_date)) : Carbon::now()->format(config('constants.DATE_FORMAT_ONLY'));

        return $sageRequest;
    }

    public static function sagePayLoad($modelType, $payment, $quote, $paymentSplits, $extras = []): object
    {
        // Reminder: Need to update the insurer details when contact and insured person FR approved
        $firstChildPayment = $paymentSplits->first();
        $insuredFullName = isset($quote->customer_id) ? $quote?->customer?->insured_first_name.' '.$quote?->customer?->insured_last_name : '';
        $latestEndorsementCode = null;
        $latestEndorsementNumber = null;
        $endorsementSubType = '';

        if ($quote?->personal_quote_id && $quote?->send_update_log_id) {
            $latestEndorsement = SendUpdateLog::where('id', $quote->send_update_log_id)->first();
            $latestEndorsementCode = $latestEndorsement?->code;
            $latestEndorsementNumber = $latestEndorsement?->endorsement_number;

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

        $personalQuote = PersonalQuote::find($quote?->personal_quote_id);

        $sageRequest = new stdClass;

        $sageRequest->quoteRefId = $personalQuote?->code ?? '';
        $sageRequest->userId = auth()->id();
        $sageRequest->invoiceDescription = $payment->invoice_description;
        $sageRequest->bookingDate = $quote?->policy_booking_date ? date(config('constants.DATE_FORMAT_ONLY'), strtotime($quote?->policy_booking_date)) : Carbon::now()->format(config('constants.DATE_FORMAT_ONLY'));
        $sageRequest->policyBookingDate = $quote?->policy_booking_date ? date(config('constants.SAGE_300_CUSTOM_API_DATE_FORMAT'), strtotime($quote?->policy_booking_date)) : Carbon::now()->format(config('constants.SAGE_300_CUSTOM_API_DATE_FORMAT'));

        // For EP Reversal, use send update's booking date instead of main lead's policy booking date
        if (isset($extras['isEPReversal']) && $extras['isEPReversal'] && $extras['sendUpdateLog']) {
            $sageRequest->bookingDate = $extras['sendUpdateLog']?->booking_date ? date(config('constants.DATE_FORMAT_ONLY'), strtotime($extras['sendUpdateLog']?->booking_date)) : Carbon::now()->format(config('constants.DATE_FORMAT_ONLY'));
            $sageRequest->policyBookingDate = $extras['sendUpdateLog']?->booking_date ? date(config('constants.SAGE_300_CUSTOM_API_DATE_FORMAT'), strtotime($extras['sendUpdateLog']?->booking_date)) : Carbon::now()->format(config('constants.SAGE_300_CUSTOM_API_DATE_FORMAT'));
        }

        $sageRequest->policyExpiryDate = $quote?->policy_expiry_date ? date(config('constants.SAGE_300_CUSTOM_API_DATE_FORMAT'), strtotime($quote->policy_expiry_date)) : '';
        $sageRequest->insurerInvoiceDate = date(config('constants.DATE_FORMAT_ONLY'), strtotime($payment->insurer_invoice_date));

        if (! empty($paymentSplits)) {
            $sageRequest->paymentDueDate = date(config('constants.DATE_FORMAT_ONLY'), strtotime($firstChildPayment->due_date));
        }

        $policyNumber = $quote?->policy_number ?? $personalQuote?->policy_number ?? '';

        $sageRequest->mainClassInsurance = $modelType;
        $sageRequest->planId = $quote->plan_id ?? null;
        $sageRequest->policyNumber = mb_substr($policyNumber, 60);
        $sageRequest->originalPolicyNumber = $policyNumber;
        $sageRequest->policyIssuer = $policyIssuer;
        $sageRequest->requestType = Lookup::where('id', $quote->transaction_type_id)->first()->text ?? '';
        $sageRequest->subClass = $businessTypeOfInsuranceCode;
        $sageRequest->ccCode = $firstChildPayment->cc_payment_id ?? '';
        $sageRequest->isPostDatedCheck = $firstChildPayment->payment_method == PaymentMethodsEnum::PostDatedCheque ? 'Yes' : 'No';
        $sageRequest->checkDetails = $firstChildPayment->check_detail ?? '';
        $sageRequest->endorsementNumber = $latestEndorsementCode;
        $sageRequest->sendUpdateEndorsementNumber = $latestEndorsementNumber;
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
        if ($quote->vat > 0) {
            $sageRequest->vatOnPremium = $quote->vat;
        } else {
            $vatPercentage = ApplicationStorage::where('key_name', ApplicationStorageEnums::VAT_VALUE)->first()?->value;
            $sageRequest->vatOnPremium = $vatPercentage && $quote->price_vat_applicable ? (($quote->price_vat_applicable * $vatPercentage) / 100) : 0;
        }
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
            // Note: Discount invoices are no longer created, so we don't include discount in collection amount
            $sageRequest->collection_amount = $firstChildPayment->collection_amount;
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
        $sageRequest->insurerPaymentGatewayId = $insuranceProvider?->payment_gateway_id;

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

    public static function createAPPrepaymentReceiptPayload($sageRequest)
    {
        $entryType = SageEnum::SCT_STRAIGHT;
        $optionalFields = self::createPrepaymentOptionalFields($sageRequest);
        $optionalFields[] = [
            'OptionalField' => 'INSURERRCTNO',
            'Value' => $sageRequest->insurerReceiptNumber ?? 'N/A',
        ];

        $vendorNumber = $sageRequest->sageVenderId;
        $bankCode = SageEnum::BANK_CODE_INS;
        $bankReceiptAmount = roundNumber(floatval($sageRequest->collection_amount), 2);

        if (in_array($sageRequest->sage_payment_code, [PaymentMethodsEnum::InsurerPayment, PaymentMethodsEnum::PostDatedCheque])) {
            $bankCode = SageEnum::BANK_CODE_INS;
        }
        $entryDescription = 'CLIENT DIRECT PAYMENT TO '.$sageRequest->insurerCode;
        $payLoad = [
            'BatchSelector' => 'PY',
            'Description' => $entryDescription,
            'BankCode' => $bankCode,
            'PaymentsAdjustments' => [
                [
                    'BatchType' => 'PY',
                    'VendorNumber' => $vendorNumber,
                    'EntryDescription' => $entryDescription,
                    'PaymentTransactionType' => 'Prepayment',
                    'BankCode' => $bankCode,
                    'TotalPrepayVendorCurrency' => $bankReceiptAmount,
                    'AppliedPayments' => [
                        [
                            'BatchType' => 'PY',
                            'VendorNumber' => $vendorNumber,
                            'TransactionType' => 'PrepaymentPosted',
                        ],
                    ],
                    'PaymentAdjustmentOptionalField' => $optionalFields,
                ],

            ],
        ];

        return [
            'endPoint' => 'AP/APPaymentAndAdjustmentBatches',
            'payload' => $payLoad,
            'sage_request_type' => SageEnum::SRT_CREATE_AP_PP_REC,
            'entry_type' => $entryType,
        ];
    }

    public static function readyToPostAPPaymentReceiptPayload($batchNumber)
    {
        $entryType = SageEnum::SCT_STRAIGHT;
        $payLoad = [
            'BatchStatus' => 'ReadyToPost',
        ];

        return [
            'endPoint' => 'AP/APPaymentAndAdjustmentBatches'.'(BatchSelector=\'PY\',BatchNumber='.$batchNumber.')',
            'payload' => $payLoad,
            'sage_request_type' => SageEnum::SRT_RTP_AP_PP_REC,
            'entry_type' => $entryType,
        ];
    }

    public static function postAPPaymentReceiptPayload($batchNumber)
    {
        $entryType = SageEnum::SCT_STRAIGHT;
        $payLoad = [
            'BatchType' => 'PY',
            'PostAllBatches' => 'Donotpostallbatches',
            'PostBatchFrom' => $batchNumber,
            'PostBatchTo' => $batchNumber,
            'ActionSelector' => 'string',
            'UpdateOperation' => 'Unspecified',

        ];

        $sign = '$process';
        $val = "('".$sign."')";

        return [
            'endPoint' => 'AP/APPostPaymentsAndAdjustments'.$val,
            'payload' => $payLoad,
            'sage_request_type' => SageEnum::SRT_POST_AP_PP_REC,
            'entry_type' => $entryType,
        ];
    }

    public static function createUpfrontApplyPaymentAPInvoicePayload($quote, $vendorNumber, $payment, $splitPayments, $isPosAllSplitPayment = false)
    {
        $entryType = SageEnum::SCT_STRAIGHT;
        $payLoad = [
            'BatchSelector' => 'PY',
            'Description' => 'CLIENT PAYMENT MAPPING',
            'BankCode' => SageEnum::BANK_CODE_INS,
            'PaymentsAdjustments' => [
                [
                    'BatchType' => 'PY',
                    'VendorNumber' => $vendorNumber,
                    'EntryDescription' => 'CLIENT PAYMENT MAPPING',
                    'PaymentTransactionType' => 'Payment',
                    'AppliedPayments' => self::createAppliedReceiptsAdjustmentsForAP($quote, $vendorNumber, $payment, $splitPayments, $isPosAllSplitPayment),
                ],
            ],
        ];

        return [
            'endPoint' => 'AP/APPaymentAndAdjustmentBatches',
            'payload' => $payLoad,
            'sage_request_type' => SageEnum::SRT_CREATE_APPLY_PAYMENT_AP_UPFRONT_INV,
            'entry_type' => $entryType,
        ];
    }

    public static function readyToPostUpfrontApplyPaymentAPInvoicePayload($batchNumber, $type = SageEnum::SCT_STRAIGHT, $useFor = SageEnum::SCT_STRAIGHT, $extras = [])
    {
        $sageRequestType = SageEnum::SRT_RTP_APPLY_PAYMENT_AP_UPFRONT_INV;
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
            'endPoint' => 'AP/APPaymentAndAdjustmentBatches'.'(BatchSelector=\'PY\',BatchNumber='.$batchNumber.')',
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType,
            'entry_type' => SageEnum::SCT_STRAIGHT,
        ];
    }

    public static function postUpfrontApplyPaymentAPInvoicePayload($batchNumber, $type = SageEnum::SCT_STRAIGHT, $useFor = SageEnum::SCT_STRAIGHT, $extras = [])
    {
        $sageRequestType = SageEnum::SRT_POST_APPLY_PAYMENT_AP_UPFRONT_INV;
        $entryType = SageEnum::SCT_STRAIGHT;
        $payLoad = [
            'BatchType' => 'PY',
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
            'endPoint' => 'AP/APPostPaymentsAndAdjustments'.$val,
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType,
            'entry_type' => $entryType,
        ];
    }

    public static function createSplitApplyPaymentAPInvoicePayload($quote, $vendorNumber, $payment, $splitPayments, $isPosAllSplitPayment = false)
    {
        $entryType = SageEnum::SCT_STRAIGHT;
        $payLoad = [
            'BatchSelector' => 'PY',
            'Description' => 'CLIENT PAYMENT MAPPING',
            'BankCode' => SageEnum::BANK_CODE_INS,
            'PaymentsAdjustments' => [
                [
                    'BatchType' => 'PY',
                    'VendorNumber' => $vendorNumber,
                    'EntryDescription' => 'CLIENT PAYMENT MAPPING',
                    'PaymentTransactionType' => 'Payment',
                    'AppliedPayments' => self::createAppliedReceiptsAdjustmentsForAP($quote, $vendorNumber, $payment, $splitPayments, $isPosAllSplitPayment),
                ],
            ],
        ];

        return [
            'endPoint' => 'AP/APPaymentAndAdjustmentBatches',
            'payload' => $payLoad,
            'sage_request_type' => SageEnum::SRT_CREATE_APPLY_PAYMENT_AP_SPLIT_INV,
            'entry_type' => $entryType,
        ];
    }

    public static function readyToPostSplitApplyPaymentAPInvoicePayload($batchNumber)
    {
        $entryType = SageEnum::SCT_STRAIGHT;
        $payLoad = [
            'BatchStatus' => 'ReadyToPost',
        ];

        return [
            'endPoint' => 'AP/APPaymentAndAdjustmentBatches'.'(BatchSelector=\'PY\',BatchNumber='.$batchNumber.')',
            'payload' => $payLoad,
            'sage_request_type' => SageEnum::SRT_RTP_APPLY_PAYMENT_AP_SPLIT_INV,
            'entry_type' => $entryType,
        ];
    }

    public static function postSplitApplyPaymentAPInvoicePayload($batchNumber, $type = SageEnum::SCT_STRAIGHT, $useFor = SageEnum::SCT_STRAIGHT, $extras = [])
    {
        $sageRequestType = SageEnum::SRT_POST_APPLY_PAYMENT_AP_SPLIT_INV;
        $entryType = $type;
        $payLoad = [
            'BatchType' => 'PY',
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
            'endPoint' => 'AP/APPostPaymentsAndAdjustments'.$val,
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType,
            'entry_type' => $entryType,
        ];
    }

    public static function createAppliedReceiptsAdjustmentsForAP($quote, $vendorNumber, $paymentRecord, $splitPaymentRecords, $isPaymentsSplit)
    {
        if ($isPaymentsSplit) {
            $receiptsAndAdjustmentsData = self::createAppliedReceiptsAdjustmentsForSplitPaymentsForAP($splitPaymentRecords, $vendorNumber, $paymentRecord);
        } else {
            $firstSplitPaymentRecord = $splitPaymentRecords[0];
            $receiptsAndAdjustmentsData = self::createAppliedReceiptsAdjustmentsForNonSplitPaymentsForAP($firstSplitPaymentRecord, $vendorNumber, $paymentRecord);
        }

        return $receiptsAndAdjustmentsData;
    }

    private static function getTermsCode($splitPaymentsCount)
    {
        return $splitPaymentsCount === 1 ? 'COD' : ($splitPaymentsCount >= 10 ? 'SPLI'.$splitPaymentsCount : 'SPLIT'.$splitPaymentsCount);
    }

    private static function createAppliedReceiptsAdjustmentsForSplitPaymentsForAR($splitPaymentRecords, $sageCustomerNumber, $paymentRecord)
    {
        $receiptsAndAdjustmentsData = [];
        foreach ($splitPaymentRecords as $index => $splitPaymentRecord) {
            if (self::isPaymentProcessed($splitPaymentRecord->payment_status_id)) {
                [$singleReceiptData, $singlePrePaymentData] = self::createReceiptDataForAR($splitPaymentRecord, $sageCustomerNumber, $paymentRecord, $index + 1);
                $receiptsAndAdjustmentsData[] = $singleReceiptData;
                $receiptsAndAdjustmentsData[] = $singlePrePaymentData;
            }

        }

        return $receiptsAndAdjustmentsData;
    }
    private static function createAppliedReceiptsAdjustmentsForNonSplitPaymentsForAR($firstSplitPaymentRecord, $sageCustomerNumber, $paymentRecord)
    {
        $receiptsAndAdjustmentsData = [];
        if (self::isPaymentProcessed($firstSplitPaymentRecord->payment_status_id)) {
            [$singleReceiptData, $singlePrePaymentData] = self::createReceiptDataForAR($firstSplitPaymentRecord, $sageCustomerNumber, $paymentRecord);
            $receiptsAndAdjustmentsData[] = $singleReceiptData;
            $receiptsAndAdjustmentsData[] = $singlePrePaymentData;
        }

        return $receiptsAndAdjustmentsData;
    }

    private static function createAppliedReceiptsAdjustmentsForSplitPaymentsForAP($splitPaymentRecords, $vendorNumber, $paymentRecord)
    {
        $receiptsAndAdjustmentsData = [];
        foreach ($splitPaymentRecords as $index => $splitPaymentRecord) {
            if (self::isPaymentProcessed($splitPaymentRecord->payment_status_id)) {
                [$singleReceiptData, $singlePrePaymentData] = self::createReceiptDataForAP($splitPaymentRecord, $vendorNumber, $paymentRecord, $index + 1);
                $receiptsAndAdjustmentsData[] = $singleReceiptData;
                $receiptsAndAdjustmentsData[] = $singlePrePaymentData;
            }

        }

        return $receiptsAndAdjustmentsData;
    }

    private static function createAppliedReceiptsAdjustmentsForNonSplitPaymentsForAP($firstSplitPaymentRecord, $vendorNumber, $paymentRecord)
    {
        $receiptsAndAdjustmentsData = [];
        if (self::isPaymentProcessed($firstSplitPaymentRecord->payment_status_id)) {
            [$singleReceiptData, $singlePrePaymentData] = self::createReceiptDataForAP($firstSplitPaymentRecord, $vendorNumber, $paymentRecord);
            $receiptsAndAdjustmentsData[] = $singleReceiptData;
            $receiptsAndAdjustmentsData[] = $singlePrePaymentData;
        }

        return $receiptsAndAdjustmentsData;
    }

    private static function createReceiptDataForAP($item, $vendorNumber, $payment, $paymentNumber = 1)
    {
        $documentNumber = mb_substr($payment->insurer_tax_number, -18);
        $receiptData = [
            'BatchType' => 'PY',
            'VendorNumber' => $vendorNumber,
            'DocumentNumber' => $documentNumber,
            'PaymentNumber' => $paymentNumber,
            'TransactionType' => 'PaymentPosted',
            // Note: Discount invoices are no longer created, so we don't include discount in payment amount
            'PaymentAmount' => roundNumber(floatval($item->payment_amount)),
        ];

        $prePaymentData = [
            'BatchType' => 'PY',
            'VendorNumber' => $vendorNumber,
            'DocumentNumber' => $item->sage_ap_payment_receipt_id,
            'PaymentNumber' => 1,
            'TransactionType' => 'PaymentPosted',
            'PaymentAmount' => -roundNumber($item->payment_amount),
        ];

        return [$receiptData, $prePaymentData];
    }

    private static function isPaymentProcessed($paymentStatusId)
    {
        return in_array($paymentStatusId, [PaymentStatusEnum::PAID, PaymentStatusEnum::CAPTURED]);
    }

    private static function applyReversalTransformations($reversePayLoad, $request, $invoiceIndex = 0)
    {
        $bookingDate = Carbon::parse($request->bookingDate)->format(self::instanceData()->sage_api_date_format);

        $reversePayLoad->Invoices[$invoiceIndex]->DocumentNumber = $reversePayLoad->Invoices[$invoiceIndex]->DocumentNumber.'-REV';
        $reversePayLoad->Invoices[$invoiceIndex]->InvoiceDescription = $reversePayLoad->Invoices[$invoiceIndex]->InvoiceDescription.' - REVERSAL';
        $reversePayLoad->Invoices[$invoiceIndex]->DocumentDate = $bookingDate;
        $reversePayLoad->Invoices[$invoiceIndex]->PostingDate = $bookingDate;
        $reversePayLoad->Invoices[$invoiceIndex]->DueDate = $bookingDate;
        $reversePayLoad->Invoices[$invoiceIndex]->AsOfDate = $bookingDate;

        if (isset($reversePayLoad->Invoices[$invoiceIndex]->InvoicePaymentSchedules)) {
            foreach ($reversePayLoad->Invoices[$invoiceIndex]->InvoicePaymentSchedules as $schedule) {
                $schedule->DueDate = $bookingDate;
            }
        }

        return $reversePayLoad;
    }

    private static function applyCorrectionTransformations($payLoad, $invoiceIndex = 0, $updateDistributionDescription = false)
    {
        $payLoad['Invoices'][$invoiceIndex]['InvoiceDescription'] = $payLoad['Invoices'][$invoiceIndex]['InvoiceDescription'].' - NEW';

        if ($updateDistributionDescription && isset($payLoad['Invoices'][$invoiceIndex]['InvoiceDetails'][0]['Description'])) {
            $payLoad['Invoices'][$invoiceIndex]['InvoiceDetails'][0]['Description'] = $payLoad['Invoices'][$invoiceIndex]['InvoiceDescription'];
        }

        if ($updateDistributionDescription && isset($payLoad['Invoices'][$invoiceIndex]['InvoiceDetails'][0]['DistributionDescription'])) {
            $payLoad['Invoices'][$invoiceIndex]['InvoiceDetails'][0]['DistributionDescription'] = $payLoad['Invoices'][$invoiceIndex]['InvoiceDescription'];
        }

        return $payLoad;
    }

    private static function prepareReversalPayload($reversalDetails)
    {
        $reversePayLoad = json_decode($reversalDetails);
        unset($reversePayLoad->BatchStatus);
        unset($reversePayLoad->BatchNumber);

        return $reversePayLoad;
    }
}
