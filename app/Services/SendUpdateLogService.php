<?php

namespace App\Services;

use App\Enums\DocumentTypeCode;
use App\Enums\quoteTypeCode;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\Payment;
use App\Models\SendUpdateLog;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\LookupRepository;

class SendUpdateLogService
{
    public function isNegativeValue($sendUpdateLog): bool
    {
        $category = LookupRepository::where('id', $sendUpdateLog->category_id)->value('code');

        if (in_array($category, [SendUpdateLogStatusEnum::CI, SendUpdateLogStatusEnum::CIR])) {
            return true;
        }

        if ($category == SendUpdateLogStatusEnum::EF) {
            $option = LookupRepository::where('id', $sendUpdateLog->option_id)->value('code');
            if (in_array($option, [
                SendUpdateLogStatusEnum::MPC,
                SendUpdateLogStatusEnum::MDOM,
                SendUpdateLogStatusEnum::MDOV,
                SendUpdateLogStatusEnum::ED,
                SendUpdateLogStatusEnum::DM,
            ])) {
                return true;
            }
        }

        return false;
    }

    public function getInvoiceDescription($sendUpdateLog, $quote, $quoteType, $insurance_provider_id)
    {
        $insuranceProviderCode = InsuranceProviderRepository::where('id', $insurance_provider_id)->value('code');
        $insuranceProviderLeadCount = Payment::where('insurance_provider_id', '=', $insurance_provider_id)->count();

        $sendUpdateLogCategory = LookupRepository::where('id', $sendUpdateLog->category_id)->value('code');

        $invoiceDescription = $insuranceProviderCode.'-'.$quoteType.'-'.$quote->policy_number;

        if (in_array($sendUpdateLogCategory, [SendUpdateLogStatusEnum::EF])) {
            $invoiceDescription = 'E.'.$invoiceDescription;
        } elseif (in_array($sendUpdateLogCategory, [SendUpdateLogStatusEnum::CI, SendUpdateLogStatusEnum::CIR])) {
            $invoiceDescription = 'CI.'.$invoiceDescription;
        } elseif ($sendUpdateLogCategory == SendUpdateLogStatusEnum::CPD) {
            $reversalInvoiceDescription = 'R.'.$invoiceDescription;
            $invoiceDescription = 'C.'.$invoiceDescription;
        }

        return [
            'broker_invoice_number' => $insuranceProviderCode.$insuranceProviderLeadCount,
            'invoice_description' => $invoiceDescription,
            'reversal_invoice_description' => $reversalInvoiceDescription ?? '',
        ];
    }

    public function getPayments($quoteId, $quoteUuid, $quoteType)
    {
        if (checkPersonalQuotes($quoteType)) {
            $repository = 'App\\Repositories\\'.$quoteType.'QuoteRepository';
            $payments = $repository::getBy('uuid', $quoteUuid)->payments;
        } else {
            $quoteServiceFile = app(getServiceObject($quoteType));
            $payments = $quoteServiceFile->getEntityPlain($quoteId)?->payments ?? null;
            if (! is_null($payments)) {
                $payments->load(['paymentStatus', 'paymentStatusLog', 'paymentMethod', 'insuranceProvider']);
            }
        }

        return $payments;
    }

    public function getReversalEntries($data): object
    {
        $payments = $this->getPayments($data['quoteId'], $data['quoteUuid'], $data['quoteType']);

        return collect($payments)->where('insurer_tax_number', $data['taxInvoiceNo'])->first();
    }

    public function getUploadedDocuments($sendUpdateLog): array
    {
        return $sendUpdateLog->documents()->pluck('document_type_code')->toArray();
    }

    public function getUpdateButtonStatus($sendUpdateLog, $quoteType): string
    {
        if (count($sendUpdateLog->details) > 0) {
            $uploadedDocuments = $this->getUploadedDocuments($sendUpdateLog);
            if (in_array($sendUpdateLog->category->code, [SendUpdateLogStatusEnum::EF, SendUpdateLogStatusEnum::EN, SendUpdateLogStatusEnum::CI, SendUpdateLogStatusEnum::CIR, SendUpdateLogStatusEnum::CPU])) {
                $requiredDocuments = [DocumentTypeCode::SEND_UPDATE_TAX_INVOICE, DocumentTypeCode::SEND_UPDATE_TAX_INVOICE_RAISED_BUYER];
                // it will check is 'tax invoice' and 'tax invoice by buyer' uploaded or not.
                if (count(array_diff($requiredDocuments, $uploadedDocuments)) > 0) {
                    return SendUpdateLogStatusEnum::SUC;
                }
            }

            // Policy Shedule documents is mandatory for all LOB's.
            /* if (in_array(DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE, $uploadedDocuments)) {
                // if it is a car, bike or health quote then it will check for 'Policy Certificate' document.
                if (in_array($quoteType, [quoteTypeCode::Car, quoteTypeCode::Bike, quoteTypeCode::Health])) {
                    if (! in_array(DocumentTypeCode::SEND_UPDATE_POLICY_CERTIFICATE, $uploadedDocuments)) {
                        return false;
                    }
                }

                return SendUpdateLogStatusEnum::SU;
            } */
        }

        return false;
    }
}
