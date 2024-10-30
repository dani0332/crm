<?php

namespace App\Http\Requests;

use App\Enums\DocumentTypeCode;
use App\Enums\PermissionsEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\SendUpdateLog;
use App\Services\CentralService;
use Illuminate\Foundation\Http\FormRequest;

class SendUpdateCustomerValidationRequest extends FormRequest
{
    protected $sendUpdate;
    protected $sendUpdateDocuemnts;

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
            'action' => 'required|string',
            'inslyMigrated' => 'boolean',
            'paymentValidated' => 'boolean',
        ];
    }

    /**
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->sendUpdate = SendUpdateLog::where('id', request()->sendUpdateId ?? '')->firstOrFail();
            $this->sendUpdateDocuemnts = $this->sendUpdate?->documents()->pluck('document_type_code');
            $category = $this->sendUpdate?->category?->code;
            $option = $this->sendUpdate?->option?->code;

            if ($this->sendUpdate->status == SendUpdateLogStatusEnum::UPDATE_BOOKING_FAILED && ! auth()->user()->can(PermissionsEnum::BOOKING_FAILED_EDIT)) {
                $validator->errors()->add('error', 'Endorsement Booking Failed! Please contact finance');
            }

            if ($this->sendUpdate->status == SendUpdateLogStatusEnum::UPDATE_BOOKING_QUEUED) {
                return $validator->errors()->add('error', 'Update booking already in queued');
            }

            if ($this->sendUpdate->status == SendUpdateLogStatusEnum::UPDATE_BOOKED) {
                return $validator->errors()->add('error', 'Update already booked');
            }

            if ($this->sendUpdate->status == SendUpdateLogStatusEnum::UPDATE_SENT_TO_CUSTOMER) {
                return $validator->errors()->add('error', 'Already sent to customer.');
            }

            if ($this->sendUpdate->quote_type_id == QuoteTypeId::Car) {
                if ($option == SendUpdateLogStatusEnum::AOCOV && empty($this->sendUpdate->car_addons)) {
                    return $validator->errors()->add('error', 'Please select Addons');
                } elseif (in_array($option, [SendUpdateLogStatusEnum::COE_NFI, SendUpdateLogStatusEnum::COE]) && empty($this->sendUpdate->emirates_id)) {
                    return $validator->errors()->add('error', 'Please select Emirate');
                } elseif (in_array($option, [SendUpdateLogStatusEnum::CISC_NFI, SendUpdateLogStatusEnum::CISC]) && empty($this->sendUpdate->seating_capacity)) {
                    return $validator->errors()->add('error', 'Please select Seating capacity');
                }
            }

            if ($category == SendUpdateLogStatusEnum::EF && $option == SendUpdateLogStatusEnum::PPE && is_null($this->sendUpdate->expiry_date)) {
                return $validator->errors()->add('error', 'The expiry date field is required.');
            }

            $bypassStatuses = [
                SendUpdateLogStatusEnum::UPDATE_BOOKING_FAILED,
                SendUpdateLogStatusEnum::UPDATE_BOOKING_QUEUED,
            ];

            $checkTransactionApprovedInSUStatusLogs = app(CentralService::class)->checkStatusSUStatusLogs($this->sendUpdate->id, SendUpdateLogStatusEnum::TRANSACTION_APPROVED);

            switch ($category) {
                case SendUpdateLogStatusEnum::CPD:
                    if (($this->sendUpdate->status != SendUpdateLogStatusEnum::TRANSACTION_APPROVED && ! $checkTransactionApprovedInSUStatusLogs) && ! in_array($this->sendUpdate->status, $bypassStatuses)) {
                        $validator->errors()->add('error', 'Transaction approval is required. ');
                    }
                    break;
                case SendUpdateLogStatusEnum::EF:
                    if (($this->sendUpdate->status != SendUpdateLogStatusEnum::TRANSACTION_APPROVED && ! $checkTransactionApprovedInSUStatusLogs) && ! in_array($this->sendUpdate->status, $bypassStatuses)) {
                        if (! in_array(
                            $option,
                            [
                                SendUpdateLogStatusEnum::MPC,
                                SendUpdateLogStatusEnum::MDOM,
                                SendUpdateLogStatusEnum::MDOV,
                                SendUpdateLogStatusEnum::ED,
                                SendUpdateLogStatusEnum::DM,
                                SendUpdateLogStatusEnum::DTSI,
                                SendUpdateLogStatusEnum::DOV,
                                SendUpdateLogStatusEnum::ATIB,
                                SendUpdateLogStatusEnum::ACB,
                            ]
                        )) {
                            $validator->errors()->add('error', 'Transaction approval is required. ');
                        }
                    }
                    if (! (in_array(DocumentTypeCode::SEND_UPDATE_POLICY_CERTIFICATE, $this->sendUpdateDocuemnts->toArray()) || in_array(
                        DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE,
                        $this->sendUpdateDocuemnts->toArray()
                    ))) {
                        $validator->errors()->add('error', 'Please upload the Endorsed schedule or Endorsed certificate. ');
                    }
                    break;
                case SendUpdateLogStatusEnum::EN:
                    if (in_array(
                        $this->sendUpdate->quoteType->code,
                        [
                            quoteTypeCode::Car,
                            quoteTypeCode::Bike,
                            quoteTypeCode::Travel,
                            quoteTypeCode::Life,
                            quoteTypeCode::Home,
                            quoteTypeCode::Pet,
                            quoteTypeCode::Cycle,
                            quoteTypeCode::Yacht,
                            quoteTypeCode::CORPLINE,
                        ]
                    )) {
                        if (! (in_array(DocumentTypeCode::SEND_UPDATE_POLICY_CERTIFICATE, $this->sendUpdateDocuemnts->toArray()) || in_array(
                            DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE,
                            $this->sendUpdateDocuemnts->toArray()
                        ))) {
                            $validator->errors()->add('error', 'Please upload documents. ');
                        }
                    } elseif ($this->sendUpdate->quoteType->code == quoteTypeCode::Health) {
                        if (in_array(
                            $option,
                            [
                                SendUpdateLogStatusEnum::CAA,
                                SendUpdateLogStatusEnum::EIU,
                                SendUpdateLogStatusEnum::MSCNFI,
                                SendUpdateLogStatusEnum::RFCOC,
                                SendUpdateLogStatusEnum::RFCOI,
                                SendUpdateLogStatusEnum::WOWPA,
                            ]
                        )) {
                            if (! (in_array(DocumentTypeCode::SEND_UPDATE_POLICY_CERTIFICATE, $this->sendUpdateDocuemnts->toArray()) || in_array(
                                DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE,
                                $this->sendUpdateDocuemnts->toArray()
                            ))) {
                                $validator->errors()->add('error', 'Please upload documents. ');
                            }
                        } elseif (in_array($option, [SendUpdateLogStatusEnum::QR, SendUpdateLogStatusEnum::RFAML, SendUpdateLogStatusEnum::RFEC])) {
                            if (! in_array(DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE, $this->sendUpdateDocuemnts->toArray())) {
                                $validator->errors()->add('error', 'Please upload documents. ');
                            }
                        }
                    } elseif ($this->sendUpdate->quoteType->code == quoteTypeCode::GroupMedical) {
                        if (in_array(
                            $option,
                            [SendUpdateLogStatusEnum::CAA, SendUpdateLogStatusEnum::RFCOC, SendUpdateLogStatusEnum::RFCOI, SendUpdateLogStatusEnum::RFTI]
                        )) {
                            if (! (in_array(DocumentTypeCode::SEND_UPDATE_POLICY_CERTIFICATE, $this->sendUpdateDocuemnts->toArray()) || in_array(
                                DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE,
                                $this->sendUpdateDocuemnts->toArray()
                            ))) {
                                $validator->errors()->add('error', 'Please upload documents. ');
                            }
                        } elseif (in_array(
                            $option,
                            [
                                SendUpdateLogStatusEnum::EIU,
                                SendUpdateLogStatusEnum::MSCNFI,
                                SendUpdateLogStatusEnum::QR,
                                SendUpdateLogStatusEnum::RFAML,
                                SendUpdateLogStatusEnum::RFSOA,
                                SendUpdateLogStatusEnum::WOWPA,
                            ]
                        )) {
                            if (! in_array(DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE, $this->sendUpdateDocuemnts->toArray())) {
                                $validator->errors()->add('error', 'Please upload documents. ');
                            }
                        }
                    }
                    break;
                case SendUpdateLogStatusEnum::CI:
                case SendUpdateLogStatusEnum::CIR: // need to discuss as per the FR4 > 5 > b > 2, missing text.
                case SendUpdateLogStatusEnum::CPU:
                    if (! (in_array(DocumentTypeCode::SEND_UPDATE_POLICY_CERTIFICATE, $this->sendUpdateDocuemnts->toArray()) || in_array(
                        DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE,
                        $this->sendUpdateDocuemnts->toArray()
                    ))) {
                        $validator->errors()->add('error', 'Please upload the Endorsed schedule or Endorsed certificate. ');
                    }
                    break;
            }
        });
    }
}
