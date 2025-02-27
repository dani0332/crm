<?php

namespace App\Http\Requests;

use App\Enums\DocumentTypeCode;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypeId;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\SendUpdateLog;
use App\Services\CentralService;
use App\Services\SageApiService;
use App\Services\SendUpdateLogService;
use Illuminate\Foundation\Http\FormRequest;

class SendUpdateValidationRequest extends FormRequest
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

            if ($sendUpdateLog->status == SendUpdateLogStatusEnum::UPDATE_BOOKING_FAILED && ! auth()->user()->can(PermissionsEnum::BOOKING_FAILED_EDIT)) {
                $validator->errors()->add('error', 'Endorsement Booking Failed! Please contact finance');
            }

            if ($sendUpdateLog->status == SendUpdateLogStatusEnum::UPDATE_BOOKING_QUEUED) {
                $validator->errors()->add('error', 'Update booking already in queued');
            }

            $checkTransactionApprovedInSUStatusLogs = app(CentralService::class)->checkStatusSUStatusLogs($sendUpdateLog->id, SendUpdateLogStatusEnum::TRANSACTION_APPROVED);

            if ($sendUpdateLog->status == SendUpdateLogStatusEnum::UPDATE_BOOKED) {
                return $validator->errors()->add('error', 'Update already booked');
            } elseif ($sendUpdateLog->status == SendUpdateLogStatusEnum::REQUEST_IN_PROGRESS) {
                if (! $checkTransactionApprovedInSUStatusLogs && ! in_array($sendUpdateLog?->option->code, [
                    SendUpdateLogStatusEnum::ATCRNB, SendUpdateLogStatusEnum::ATCRNB_RBB, SendUpdateLogStatusEnum::ATCRN_CRNRBB])) {
                    $validator->errors()->add('error', 'Transaction approval is required');
                }
            }

            $sendUpdateCategoryCode = $sendUpdateLog?->category->code ?? '';
            $categorySubType = $sendUpdateLog?->option->code ?? '';
            $uploadedDocuments = $sendUpdateLog?->documents()->pluck('document_type_code')->toArray();

            if ($sendUpdateLog->quote_type_id == QuoteTypeId::Car) {
                if ($sendUpdateLog->option?->code == SendUpdateLogStatusEnum::AOCOV && empty($sendUpdateLog->car_addons)) {
                    return $validator->errors()->add('error', 'Please select Addons');
                } elseif ($sendUpdateLog->option?->code == SendUpdateLogStatusEnum::COE && empty($sendUpdateLog->emirates_id)) {
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
                            SendUpdateLogStatusEnum::I_CLILLR,
                            SendUpdateLogStatusEnum::IEAF_T,
                            SendUpdateLogStatusEnum::IISI,
                            SendUpdateLogStatusEnum::PPE,
                        ])) {
                            if (count(array_intersect($uploadedDocuments, $requiredDocuments)) < count($requiredDocuments)) {
                                return $validator->errors()->add('error', 'Please upload tax invoice and tax invoice raised by buyer');
                            }
                        }

                        if (in_array($categorySubType, [
                            SendUpdateLogStatusEnum::MPC,
                        ])) {
                            if (count(array_intersect($uploadedDocuments, $requiredDocumentsForMPC)) < count($requiredDocumentsForMPC)) {
                                return $validator->errors()->add('error', 'Please upload tax invoice and tax invoice raised by buyer');
                            }
                        }
                        break;
                }
            }

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

                // Check all policy details have been correctly filled
                if (($sendUpdateCategoryCode == SendUpdateLogStatusEnum::EF && $categorySubType == SendUpdateLogStatusEnum::PPE) ||
                    $sendUpdateCategoryCode == SendUpdateLogStatusEnum::CPD) {
                    if (! $sendUpdateLog->is_policy_filled) {
                        $validator->errors()->add('error', 'Please update the missing policy details');
                    }
                }

                $bypassStatuses = [
                    SendUpdateLogStatusEnum::UPDATE_BOOKING_FAILED,
                    SendUpdateLogStatusEnum::UPDATE_BOOKING_QUEUED,
                ];

                if ($sendUpdateCategoryCode == SendUpdateLogStatusEnum::EF && ! $checkTransactionApprovedInSUStatusLogs &&
                    ! in_array($sendUpdateLog->status, [SendUpdateLogStatusEnum::TRANSACTION_APPROVED, SendUpdateLogStatusEnum::UPDATE_SENT_TO_CUSTOMER]) &&
                    ! in_array($sendUpdateLog->status, $bypassStatuses) &&
                    ! in_array($categorySubType, [
                        SendUpdateLogStatusEnum::MPC,
                        SendUpdateLogStatusEnum::MDOM,
                        SendUpdateLogStatusEnum::MDOV,
                        SendUpdateLogStatusEnum::ED,
                        SendUpdateLogStatusEnum::DM,
                        SendUpdateLogStatusEnum::DTSI,
                        SendUpdateLogStatusEnum::DOV,
                        SendUpdateLogStatusEnum::ATIB,
                        SendUpdateLogStatusEnum::ATICB,
                        SendUpdateLogStatusEnum::ACB,
                        SendUpdateLogStatusEnum::ATCRNB,
                        SendUpdateLogStatusEnum::ATCRNB_RBB,
                        SendUpdateLogStatusEnum::ATCRN_CRNRBB,
                    ])) {
                    $validator->errors()->add('error', 'Transaction approval is required');
                }
            }

            if (! (new SageApiService)->isSageEnabled()) {
                return ['status' => false, 'message' => 'Sage300 is not enabled'];
            }

            if (app(SendUpdateLogService::class)->isPaymentVisible($sendUpdateCategoryCode, $categorySubType) && $sendUpdateLog->payments->isEmpty()) {
                $validator->errors()->add('error', 'Please add payment details');
            }
        });
    }
}
