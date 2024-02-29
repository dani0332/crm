<?php

namespace App\Http\Requests;

use App\Enums\DocumentTypeCode;
use App\Enums\quoteTypeCode;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\SendUpdateLog;
use Illuminate\Foundation\Http\FormRequest;

class SendUpdateCustomerRequest extends FormRequest
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
            $category = $this->sendUpdate->category->code;
            $option = $this->sendUpdate->option->code;

            if ($this->sendUpdateDocuemnts->count()) {
                if ($category == SendUpdateLogStatusEnum::EF) {
                    if ($this->sendUpdate->status != SendUpdateLogStatusEnum::TRANSACTION_APPROVED) {
                        if (! in_array($option, [SendUpdateLogStatusEnum::MPC, SendUpdateLogStatusEnum::MDOM, SendUpdateLogStatusEnum::MDOV, SendUpdateLogStatusEnum::ED, SendUpdateLogStatusEnum::DM])) {
                            $validator->errors()->add('error', 'Transaction approval is required. ');
                        }
                    }
                    if (! (in_array(DocumentTypeCode::SEND_UPDATE_POLICY_CERTIFICATE, $this->sendUpdateDocuemnts->toArray()) || in_array(DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE, $this->sendUpdateDocuemnts->toArray()))) {
                        $validator->errors()->add('error', 'Please upload the Endorsed schedule or Endorsed certificate. ');
                    }
                } elseif ($category == SendUpdateLogStatusEnum::EN) {
                    if (in_array($this->sendUpdate->quoteType->code, [quoteTypeCode::Car, quoteTypeCode::Bike, quoteTypeCode::Travel, quoteTypeCode::Life, quoteTypeCode::Home, quoteTypeCode::Pet, quoteTypeCode::Cycle, quoteTypeCode::Yacht, quoteTypeCode::CORPLINE])) {
                        if (! (in_array(DocumentTypeCode::SEND_UPDATE_POLICY_CERTIFICATE, $this->sendUpdateDocuemnts->toArray()) || in_array(DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE, $this->sendUpdateDocuemnts->toArray()))) {
                            $validator->errors()->add('error', 'Please upload documents. ');
                        }
                    } elseif ($this->sendUpdate->quoteType->code == quoteTypeCode::Health) {
                        if (in_array($option, [SendUpdateLogStatusEnum::CAA, SendUpdateLogStatusEnum::EIU, SendUpdateLogStatusEnum::MSCNFI, SendUpdateLogStatusEnum::RFCOC, SendUpdateLogStatusEnum::RFCOI, SendUpdateLogStatusEnum::WOWPA])) {
                            if (! (in_array(DocumentTypeCode::SEND_UPDATE_POLICY_CERTIFICATE, $this->sendUpdateDocuemnts->toArray()) || in_array(DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE, $this->sendUpdateDocuemnts->toArray()))) {
                                $validator->errors()->add('error', 'Please upload documents. ');
                            }
                        } elseif (in_array($option, [SendUpdateLogStatusEnum::QR, SendUpdateLogStatusEnum::RFAML, SendUpdateLogStatusEnum::RFEC])) {
                            if (! in_array(DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE, $this->sendUpdateDocuemnts->toArray())) {
                                $validator->errors()->add('error', 'Please upload documents. ');
                            }
                        }
                    } elseif ($this->sendUpdate->quoteType->code == quoteTypeCode::GroupMedical) {
                        if (in_array($option, [SendUpdateLogStatusEnum::CAA, SendUpdateLogStatusEnum::RFCOC, SendUpdateLogStatusEnum::RFCOI, SendUpdateLogStatusEnum::RTI])) {
                            if (! (in_array(DocumentTypeCode::SEND_UPDATE_POLICY_CERTIFICATE, $this->sendUpdateDocuemnts->toArray()) || in_array(DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE, $this->sendUpdateDocuemnts->toArray()))) {
                                $validator->errors()->add('error', 'Please upload documents. ');
                            }
                        } elseif (in_array($option, [SendUpdateLogStatusEnum::EIU, SendUpdateLogStatusEnum::MSCNFI, SendUpdateLogStatusEnum::QR, SendUpdateLogStatusEnum::RFAML, SendUpdateLogStatusEnum::RFSOA, SendUpdateLogStatusEnum::WOWPA])) {
                            if (! in_array(DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE, $this->sendUpdateDocuemnts->toArray())) {
                                $validator->errors()->add('error', 'Please upload documents. ');
                            }
                        }
                    }
                } elseif (in_array($category, [SendUpdateLogStatusEnum::CI, SendUpdateLogStatusEnum::CIR, SendUpdateLogStatusEnum::CPU])) {
                    if (! (in_array(DocumentTypeCode::SEND_UPDATE_POLICY_CERTIFICATE, $this->sendUpdateDocuemnts->toArray()) || in_array(DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE, $this->sendUpdateDocuemnts->toArray()))) {
                        $validator->errors()->add('error', 'Please upload the Endorsed schedule or Endorsed certificate. ');
                    }
                }
            }
        });
    }
}
