<?php

namespace App\Http\Requests;

use App\Enums\DocumentTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\SendUpdateLog;
use Illuminate\Foundation\Http\FormRequest;

class SendUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sendUpdateId' => 'required|exists:send_update_logs,id',
        ];
    }

    /**
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $sendUpdateLog = SendUpdateLog::where('id', request()->sendUpdateId ?? '')->firstOrFail();

            if ($sendUpdateLog->status == SendUpdateLogStatusEnum::UPDATE_BOOKED) {
                return $validator->errors()->add('error', 'Update already booked');
            }

            $sendUpdateCategoryCode = $sendUpdateLog?->category->code ?? '';
            $categorySubType = $sendUpdateLog?->option->code ?? '';
            $uploadedDocuments = $sendUpdateLog?->documents()->pluck('document_type_code')->toArray();

            if ($sendUpdateLog->quote_type_id == QuoteTypeId::Car) {
                if ($sendUpdateLog->option?->code == SendUpdateLogStatusEnum::AOCOV && empty($sendUpdateLog->car_addons)) {
                    return $validator->errors()->add('error', 'Please select Addons');
                } elseif ($sendUpdateLog->option?->code == SendUpdateLogStatusEnum::COE && empty($sendUpdateLog->emirates_registration)) {
                    return $validator->errors()->add('error', 'Please select Emirate');
                } elseif ($sendUpdateLog->option?->code == SendUpdateLogStatusEnum::CISC && empty($sendUpdateLog->seating_capacity)) {
                    return $validator->errors()->add('error', 'Please select Seating capacity');
                }
            }

            if (in_array($sendUpdateCategoryCode, [
                SendUpdateLogStatusEnum::EF,
            ])) {
                switch ($sendUpdateLog->quote_type_id) {
                    case QuoteTypeId::Business:

                        $requiredDocuments = [
                            DocumentTypeCode::SEND_UPDATE_TAX_INVOICE,
                            DocumentTypeCode::SEND_UPDATE_TAX_INVOICE_RAISED_BUYER,
                            DocumentTypeCode::SEND_UPDATE_RECEIPT,
                        ];
                        $requiredDocumentsForMPC = [
                            DocumentTypeCode::SEND_UPDATE_TAX_INVOICE,
                            DocumentTypeCode::SEND_UPDATE_TAX_INVOICE_RAISED_BUYER,
                        ];

                        if (in_array($categorySubType, [
                            SendUpdateLogStatusEnum::MAOM,
                            SendUpdateLogStatusEnum::MDOM,
                            SendUpdateLogStatusEnum::MD,
                            SendUpdateLogStatusEnum::MSC,
                            SendUpdateLogStatusEnum::PU,
                            SendUpdateLogStatusEnum::SC,
                            SendUpdateLogStatusEnum::AOLOPFMP,
                            SendUpdateLogStatusEnum::AC,
                            SendUpdateLogStatusEnum::AL,
                            SendUpdateLogStatusEnum::EA,
                            SendUpdateLogStatusEnum::ED,
                            SendUpdateLogStatusEnum::EFMP,
                            SendUpdateLogStatusEnum::ICOLOIALOLR,
                            SendUpdateLogStatusEnum::IIEAFT,
                            SendUpdateLogStatusEnum::IISI,
                            SendUpdateLogStatusEnum::PPE,
                        ])) {
                            if (count(array_intersect($uploadedDocuments, $requiredDocuments)) == count($requiredDocuments)) {
                                return $validator->errors()->add('error', 'Please upload tax invoice and tax invoice raised by buyer and receipt');
                            }
                        }

                        if (in_array($categorySubType, [
                            SendUpdateLogStatusEnum::MPC,
                        ])) {
                            if (count(array_intersect($uploadedDocuments, $requiredDocumentsForMPC)) == count($requiredDocumentsForMPC)) {
                                return $validator->errors()->add('error', 'Please upload tax invoice and tax invoice raised by buyer');
                            }
                        }
                        break;
                }
            }

            // if (in_array($sendUpdateCategoryCode, [
            //     SendUpdateLogStatusEnum::EF,
            //     SendUpdateLogStatusEnum::CI,
            //     SendUpdateLogStatusEnum::CIR,
            // ])) {
            //     $mandatoryDocuments = [DocumentTypeCode::SEND_UPDATE_TAX_INVOICE, DocumentTypeCode::SEND_UPDATE_TAX_INVOICE_RAISED_BUYER];
            //     // check if all mandatory documents uploaded
            //     if (count(array_intersect($uploadedDocuments, $mandatoryDocuments)) == count($mandatoryDocuments)) {

            //         // Check if any of these documents uploaded
            //         $optionalDocuments = [DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE, DocumentTypeCode::SEND_UPDATE_POLICY_CERTIFICATE];
            //         if (count(array_intersect($uploadedDocuments, $optionalDocuments)) > 0) {
            //             switch ($sendUpdateLog->quote_type_id) {
            //                 case QuoteTypeId::Car:
            //                 case QuoteTypeId::Bike:
            //                 case QuoteTypeId::Health:

            //                     $_error = 'policy handbook';
            //                     $requiredDocuments = [
            //                         DocumentTypeCode::SEND_UPDATE_POLICY_HANDBOOK,
            //                     ];

            //                     if ($sendUpdateLog->quote_type_id == QuoteTypeId::Health) {
            //                         $requiredDocuments = array_merge($requiredDocuments,
            //                             [DocumentTypeCode::SEND_UPDATE_NETWORK_LIST, DocumentTypeCode::SEND_UPDATE_SIGNED_MED_APP_FORM]
            //                         );
            //                         $_error = 'policy handbook, network list and signed medical application form';
            //                     }

            //                     if (count(array_intersect($uploadedDocuments, $requiredDocuments)) !== count($requiredDocuments)) {
            //                         $validator->errors()->add('error', 'Please upload '.$_error);
            //                     }
            //                     break;

            //                 case QuoteTypeId::Travel:
            //                 case QuoteTypeId::Home:
            //                 case QuoteTypeId::Pet:
            //                 case QuoteTypeId::Cycle:
            //                 case QuoteTypeId::Yacht:
            //                 case QuoteTypeId::Business:
            //                 case QuoteTypeId::Life:

            //                     if ($sendUpdateLog->quote_type_id == QuoteTypeId::Yacht) {
            //                         $requiredDocuments = [
            //                             DocumentTypeCode::SEND_UPDATE_RECEIPT,
            //                             DocumentTypeCode::SEND_UPDATE_POLICY_HANDBOOK,
            //                         ];

            //                         if (count(array_intersect($uploadedDocuments, $requiredDocuments)) !== count($requiredDocuments)) {
            //                             $validator->errors()->add('error', 'Please upload receipt and policy handbook');
            //                         }
            //                     }

            //                     if ($sendUpdateLog->quote_type_id == QuoteTypeId::Life) {
            //                         $requiredDocuments = [
            //                             DocumentTypeCode::SEND_UPDATE_POLICY_HANDBOOK,
            //                             DocumentTypeCode::SEND_UPDATE_APP_COPY,
            //                         ];

            //                         if (count(array_intersect($uploadedDocuments, $requiredDocuments)) !== count($requiredDocuments)) {
            //                             $validator->errors()->add('error', 'Please upload application copy and policy handbook');
            //                         }
            //                     }

            //                     if ($sendUpdateLog->quote_type_id == QuoteTypeId::Business) {
            //                         $requiredDocuments = [
            //                             DocumentTypeCode::SEND_UPDATE_RECEIPT,
            //                             DocumentTypeCode::SEND_UPDATE_NETWORK_LIST,
            //                         ];

            //                         if (count(array_intersect($uploadedDocuments, $requiredDocuments)) !== count($requiredDocuments)) {
            //                             $validator->errors()->add('error', 'Please upload receipt and network list');
            //                         }
            //                     }
            //                     break;
            //             }
            //         } else {
            //             $validator->errors()->add('error', 'Please upload endorsed schedule or endorsed certificate');
            //         }
            //     } else {
            //         $validator->errors()->add('error', 'Please upload tax invoice and tax invoice raised by buyer');
            //     }
            // }

            if (in_array($sendUpdateCategoryCode, [
                SendUpdateLogStatusEnum::EF,
                SendUpdateLogStatusEnum::CI,
                SendUpdateLogStatusEnum::CIR,
                SendUpdateLogStatusEnum::CPD,
            ])) {

                // Check all booking details have been correctly filled
                if (! $sendUpdateLog->is_booking_filled) {
                    $validator->errors()->add('error', 'Please update the missing booking details');
                }

                // Check all policy details have been corretly filled
                if (($sendUpdateCategoryCode == SendUpdateLogStatusEnum::EF && $categorySubType == SendUpdateLogStatusEnum::PPE) ||
                    $sendUpdateCategoryCode == SendUpdateLogStatusEnum::CPD) {
                    if (! $sendUpdateLog->is_policy_filled) {
                        $validator->errors()->add('error', 'Please update the missing policy details');
                    }
                }

                if ($sendUpdateCategoryCode == SendUpdateLogStatusEnum::EF &&
                    ! in_array($sendUpdateLog->status, [SendUpdateLogStatusEnum::TRANSACTION_APPROVED, SendUpdateLogStatusEnum::UPDATE_SENT_TO_CUSTOMER]) &&
                    in_array($categorySubType, [
                        SendUpdateLogStatusEnum::MPC,
                        SendUpdateLogStatusEnum::MDOM,
                        SendUpdateLogStatusEnum::MDOV,
                        SendUpdateLogStatusEnum::ED,
                        SendUpdateLogStatusEnum::DM,
                    ])) {
                    $validator->errors()->add('error', 'Transaction approval is required');
                }
            }

        });
    }
}
