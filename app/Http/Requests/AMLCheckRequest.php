<?php

namespace App\Http\Requests;

use App\Enums\CustomerTypeEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Services\Logger\LoggerService;
use Illuminate\Foundation\Http\FormRequest;

class AMLCheckRequest extends FormRequest
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
        LoggerService::info('AML Check Request - Validation Rules');
        $rules = [];
        if ($this->customer_type == CustomerTypeEnum::Individual) {
            LoggerService::info('AML Check Request - Individual Customer Validation');
            $rules = [
                'nationality_id' => 'required',
                'dob' => 'required',
                'insured_first_name' => 'required|max:200',
                'insured_last_name' => 'required|max:200',
            ];

            if (in_array($this->quote_type, [QuoteTypes::CAR->value, QuoteTypes::BIKE->value, QuoteTypes::HOME->value])) {
                LoggerService::info('AML Check Request - Email Validation');
                $rules['get_quote_email_gig'] = 'nullable|email:rfc,dns';
            }
        }

        if ($this->customer_type == CustomerTypeEnum::Entity) {
            LoggerService::info('AML Check Request - Entity Customer Validation');
            $rules = [
                'trade_license_no' => 'required|max:200',
                'company_name' => 'required|max:200',
                'company_address' => 'required',
                'entity_type_code' => 'nullable',
                'industry_type_code' => 'nullable',
                'emirate_of_registration_id' => 'nullable',
            ];
        }

        $rules['customer_type'] = 'required|string';

        if ( $this->quote_type == QuoteTypes::CAR->value ) {
            LoggerService::info('AML Check Request - Chassis Number Required');
            $rules['chassis_number'] = 'required|string|min:8|max:17|regex:/^[a-zA-Z0-9]+$/';
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        LoggerService::info('AML Check Request - With Validator');
        $validator->after(function ($validator) {
            if (! auth()->user()->can(PermissionsEnum::AMLList)) {
                LoggerService::error('AML Check Request - Permission Denied');
                $validator->errors()->add('error', 'You don\'t have permission to edit this section.');
            }
        });
    }

    public function messages(): array
    {
        LoggerService::info('AML Check Request - Messages');
        return [
            'chassis_number' => 'The entered value does not meet the required length of 8 to 17 characters. Please check and confirm',
            'get_quote_email_gig' => 'Email in GIG portal must be a valid email address',
        ];
    }
}
