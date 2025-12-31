<?php

namespace App\Http\Requests;

use App\Enums\InsuranceProviderEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentGatewayEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\quoteTypeCode;
use App\Models\DocumentType;
use App\Models\InsuranceProvider;
use App\Rules\ValidateBase64;
use App\Services\MetLife\MetLifeValidationService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Foundation\Http\FormRequest;

// This validation belongs to API side while we upload document from ECOM side
class QuoteDocumentRequest extends FormRequest
{
    use GenericQueriesAllLobs;

    protected $documentType;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'file' => 'required|file',
            'document_type_code' => 'required|exists:document_types,code,is_active,1',
            'quote_uuid' => 'required',
            'member_detail_id' => 'nullable',
            'is_base_64' => 'nullable',
            'document_category' => 'nullable',
            'file_name' => 'nullable|string|max:100', // only for base 64 file name to be used as original name
            'provider_code' => 'nullable|string|max:10',
            'policy_number' => 'nullable|string|max:100',
        ];

        if (! empty(request()->document_type_code) && ($this->documentType = DocumentType::where('code', request()->document_type_code)->first())) {
            if (! (request()->is_base_64)) {
                $rules['file'] = 'mimes:'.(str_replace('.', '', $this->documentType->accepted_files)).'|max:'.($this->documentType->max_size * 1024);
            }
        }

        if (request()->is_base_64) {
            $rules['file'] = ['required', new ValidateBase64($this->documentType)];
        } else {
            $rules['file'] .= '|required|file';
        }

        return $rules;
    }

    /**
     * validate quote record and maximum number of alread uploaded files
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $quoteType = ucfirst(request()->quoteType);
            $memberDetailId = request()->member_detail_id;
            $documentTypeCode = request()->document_type_code;
            $quoteTypes = [quoteTypeCode::Health, quoteTypeCode::Travel];

            // check for quote records if exists
            if (! $quote = $this->getQuoteObject(request()->quoteType, request()->quote_uuid)) {
                $validator->errors()->add('type', 'Invalid quote type or uuid provided');

                return;
            }

            // check for maximum number of files uploaded against selected quote and document type
            if ($this->documentType && $quote->documents->where('document_type_code', $documentTypeCode)->count() >= $this->documentType->max_files) {
                $validator->errors()->add('file', 'You can only upload a maximum of '.$this->documentType->max_files.' files');

                return; // Stop validation if max files exceeded
            }

            /**
             * documents can be attached to a member for health & travel
             */
            if (in_array($quoteType, $quoteTypes) && ! empty($memberDetailId)) {
                // check for quote records if exists
                if (! $quote->customerMembers()->where('id', $memberDetailId)->first()) {
                    $validator->errors()->add('member_detail_id', 'Invalid member detail id provided');
                }
            }

            if (! in_array($quoteType, $quoteTypes) && ! empty($memberDetailId)) {
                $validator->errors()->add('member_detail_id', 'Member can be attached only for Health Insurance type');
            }

            // For health LOB we can bypass the payment validation
            if ($quoteType == quoteTypeCode::Health) {
                if (empty($quote->plan_id)) {
                    $validator->errors()->add('type', 'Documents can be uploaded once plan is selected.');
                }

                return; // Skip payment validation for health quotes with plan
            }

            $leadSource = data_get($quote, 'source', '');

            $metLifeValidator = new MetLifeValidationService;

            // Skip payment validation for MetLife (MTL) only if MetLife integration is enabled
            if ($metLifeValidator->shouldValidatePayment(request()->provider_code)) {
                if ($leadSource == LeadSourceEnum::DUBAI_NOW) {
                    // validate if payment is authorized capture or partial capture
                    if (isset($quote->payment_status_id) && ! in_array($quote->payment_status_id, [PaymentStatusEnum::AUTHORISED, PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED])) {
                        $validator->errors()->add('type', 'Documents can be uploaded once payment is authorized, captured or partial captured.');
                    }
                } else {
                    // validate if payment is authorized
                    if (request()->quoteType != strtolower(quoteTypeCode::Travel) && isset($quote->insurance_provider_id)) {
                        if (! $this->isPlanBProviderSelected($quote)) {
                            if (empty($quote->payment) ||
                                ($quote->payment->payment_status_id != PaymentStatusEnum::AUTHORISED &&
                                 $quote->payment->payment_gateway_id != PaymentGatewayEnum::PAYMENT_GATEWAY_PAYMENT_LINK)
                            ) {
                                $validator->errors()->add('type', 'Documents can be uploaded once payment is authorized.');
                            }
                        }
                    }
                }
            }
        });
    }

    private function isPlanBProviderSelected($quote)
    {
        $insuranceProvider = InsuranceProvider::find($quote->insurance_provider_id);

        $insurersWithoutCCRenewal = [
            InsuranceProviderEnum::AXA->value,    // GIG_INSURANCE
            InsuranceProviderEnum::EI->value,     // EMIRATES_INSURANCE
            InsuranceProviderEnum::RSA->value,    // LIVANA_INSURANCE
            InsuranceProviderEnum::OIC->value,    // SUKOON_OMAN_INSURANCE
        ];

        if (! $insuranceProvider) {
            return false;
        }
        if (! in_array($insuranceProvider->code, $insurersWithoutCCRenewal) && in_array($insuranceProvider->payment_gateway_id, [PaymentGatewayEnum::PAYMENT_GATEWAY_TAP, PaymentGatewayEnum::PAYMENT_GATEWAY_CHECKOUT])) {
            return false;
        }

        return true;
    }
}
