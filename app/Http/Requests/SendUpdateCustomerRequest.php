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

            $documents = $this->sendUpdateDocuemnts->toArray();
            $LOBs = array_diff(newUi(), array(quoteTypeCode::Health, quoteTypeCode::Business, quoteTypeCode::Aml));

            if($this->sendUpdateDocuemnts->count()) {
                switch ($category) {
                    case SendUpdateLogStatusEnum::EF:
                        if ($this->sendUpdate->status != SendUpdateLogStatusEnum::TRANSACTION_APPROVED) {
                            if (! in_array($option, [SendUpdateLogStatusEnum::MPC, SendUpdateLogStatusEnum::MDOM, SendUpdateLogStatusEnum::MDOV, SendUpdateLogStatusEnum::ED, SendUpdateLogStatusEnum::DM])) {
                                $validator->errors()->add('error', 'Transaction approval is required. ');
                            }
                        }
                        if ($this->checkDocuments(null, $documents, SendUpdateLogStatusEnum::EF)) {
                            $validator->errors()->add('error', 'Please upload the Endorsed schedule or Endorsed certificate.');
                        }
                        break;

                    case SendUpdateLogStatusEnum::EN:
                        if (in_array($this->sendUpdate->status, $LOBs)) {
                            if ($this->checkDocuments(null, $documents, SendUpdateLogStatusEnum::EN)) {
                                $validator->errors()->add('error', 'Please upload documents.');
                            }
                        } elseif($this->sendUpdate->quoteType->code == quoteTypeCode::Health) {
                            if($this->optionCheck(quoteTypeCode::Health, $documents, $option)) {
                                $validator->errors()->add('error', 'Please upload documents.');
                            }
                        } elseif($this->sendUpdate->quoteType->code == quoteTypeCode::GroupMedical) {
                            if($this->optionCheck(quoteTypeCode::GroupMedical, $documents, $option)) {
                                $validator->errors()->add('error', 'Please upload documents.');
                            }
                        }
                        break;

                    case SendUpdateLogStatusEnum::CI:
                    case SendUpdateLogStatusEnum::CIR:
                    case SendUpdateLogStatusEnum::CPU:
                        if ($this->checkDocuments(null, $documents, $category)) {
                            $validator->errors()->add('error', 'Please upload the Endorsed schedule or Endorsed certificate.');
                        }
                        break;
                    
                }
            }
        });
    }

    private function optionCheck($quoteType, $documents, $option) 
    {
        switch ($quoteType) {
            case quoteTypeCode::Health:
                if (in_array($option, [
                    SendUpdateLogStatusEnum::CAA,
                    SendUpdateLogStatusEnum::EIU,
                    SendUpdateLogStatusEnum::MSCNFI,
                    SendUpdateLogStatusEnum::RFCOC,
                    SendUpdateLogStatusEnum::RFCOI,
                    SendUpdateLogStatusEnum::WOWPA,
                    SendUpdateLogStatusEnum::QR,
                    SendUpdateLogStatusEnum::RFAML,
                    SendUpdateLogStatusEnum::RFEC
                ])) {
                    return $this->checkDocuments(quoteTypeCode::Health, $documents, $option);
                }
                break;

            case quoteTypeCode::GroupMedical:
                if (in_array($option, [
                    SendUpdateLogStatusEnum::CAA, 
                    SendUpdateLogStatusEnum::EIU, 
                    SendUpdateLogStatusEnum::MSCNFI, 
                    SendUpdateLogStatusEnum::RFCOC, 
                    SendUpdateLogStatusEnum::RFCOI, 
                    SendUpdateLogStatusEnum::WOWPA, 
                    SendUpdateLogStatusEnum::QR, 
                    SendUpdateLogStatusEnum::RFAML, 
                    SendUpdateLogStatusEnum::RTI, 
                    SendUpdateLogStatusEnum::RFSOA
                ])) {
                    return $this->checkDocuments(quoteTypeCode::GroupMedical, $documents, $option);
                }
                break;
        }
    }

    private function checkDocuments($quoteTypeCode, $documents, $category)
    {
        switch ($quoteTypeCode) {
            case quoteTypeCode::Health:
                switch ($category) {
                    case SendUpdateLogStatusEnum::CAA: 
                    case SendUpdateLogStatusEnum::EIU: 
                    case SendUpdateLogStatusEnum::MSCNFI:
                    case SendUpdateLogStatusEnum::RFCOC: 
                    case SendUpdateLogStatusEnum::RFCOI: 
                    case SendUpdateLogStatusEnum::WOWPA: 
                        $requiredDocumentTypes = [DocumentTypeCode::SEND_UPDATE_POLICY_CERTIFICATE, DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE];
                        
                        return count(array_intersect($documents, $requiredDocumentTypes)) != 0;
                        break;

                    case SendUpdateLogStatusEnum::QR: 
                    case SendUpdateLogStatusEnum::RFAML: 
                    case SendUpdateLogStatusEnum::RFEC:  
                        $requiredDocumentTypes = [DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE];
                        
                        return count(array_intersect($documents, $requiredDocumentTypes)) != 0;
                        break;
                }
                break;

            case quoteTypeCode::GroupMedical:
                switch ($category) {
                    case SendUpdateLogStatusEnum::CAA: 
                    case SendUpdateLogStatusEnum::RFCOC: 
                    case SendUpdateLogStatusEnum::RFCOI: 
                    case SendUpdateLogStatusEnum::RTI:
                        $requiredDocumentTypes = [DocumentTypeCode::SEND_UPDATE_POLICY_CERTIFICATE, DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE];
                        
                        return count(array_intersect($documents, $requiredDocumentTypes)) != 0;
                        break;

                    case SendUpdateLogStatusEnum::EIU: 
                    case SendUpdateLogStatusEnum::MSCNFI: 
                    case SendUpdateLogStatusEnum::QR: 
                    case SendUpdateLogStatusEnum::RFAML: 
                    case SendUpdateLogStatusEnum::RFSOA: 
                    case SendUpdateLogStatusEnum::WOWPA: 
                        $requiredDocumentTypes = [DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE];
                        
                        return count(array_intersect($documents, $requiredDocumentTypes)) != 0;
                        break;
                }
                break;
            
            default:
                switch ($category) {
                    case SendUpdateLogStatusEnum::CI:
                    case SendUpdateLogStatusEnum::CIR:
                    case SendUpdateLogStatusEnum::CPU:
                    case SendUpdateLogStatusEnum::EF:
                    case SendUpdateLogStatusEnum::EN:
                        $requiredDocumentTypes = [DocumentTypeCode::SEND_UPDATE_POLICY_CERTIFICATE, DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE];
                        
                        return count(array_intersect($documents, $requiredDocumentTypes)) != 0;
                        break;
                }
                break;
        }
    }
}
