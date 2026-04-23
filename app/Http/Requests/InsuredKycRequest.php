<?php

namespace App\Http\Requests;

use App\Enums\CarRegistrationType;
use App\Enums\CustomerTypeEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Models\Payment;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class InsuredKycRequest extends FormRequest
{
    use GenericQueriesAllLobs;

    /**
     * Customer type determined from the insured record.
     */
    protected $customerType = null;

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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Common fields for both Individual and Entity
        $rules = [
            'insured_id' => 'required|exists:insured,id',
            'quote_uuid' => 'required',
            'quote_type_id' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
            'mobile_number' => 'required',
            'email' => 'required',
            'id_number' => 'required',
            'id_issue_date' => 'required',
            'id_expiry_date' => 'required|date|after:today',
            'pep' => 'sometimes',
            'financial_sanctions' => 'sometimes',
            'dual_nationality' => 'sometimes',
            'in_sanction_list' => 'sometimes',
            'deal_sanction_list' => 'sometimes',
            'is_operation_high_risk' => 'sometimes',
            'mode_of_contact' => 'sometimes',
            'mode_of_delivery' => 'sometimes',
            'customer_tenure' => 'sometimes',
            'residential_address' => 'required',
            'id_type' => 'required',
            'website' => 'nullable',
            'customer_type' => 'nullable',
        ];

        // If customer type is Individual, add Individual-specific rules
        if (request()->customer_type === CustomerTypeEnum::Individual) {
            $individualRules = [
                'dob' => 'required',
                'nationality_id' => 'required',
                'country_of_residence' => 'required',
                'place_of_birth' => 'required',
                'resident_status' => 'required',
                'income_source' => 'required',
                'company_name' => 'required',
                'professional_title' => 'required_if:income_source,employed',
                'employment_sector' => 'required_if:income_source,employed',
                'trade_license' => 'required_if:income_source,business',
                'company_position' => 'required_if:income_source,business',
                'premium_tenure' => 'sometimes',
                'is_partner' => 'sometimes',
            ];

            $rules = array_merge($rules, $individualRules);
        }

        // If customer type is Entity, add Entity-specific rules
        if (request()->customer_type === CustomerTypeEnum::Entity) {
            $entityRules = [
                'company_name' => 'required',
                'legal_structure' => 'required',
                'industry_type' => 'required',
                'country_of_corporation' => 'required',
                'communication_address' => 'required',
                'place_of_issue' => 'required',
                'issuing_authority' => 'required',
                'manager_name' => 'required',
                'manager_nationality' => 'required',
                'manager_dob' => 'required',
                'manager_position' => 'required',
                'in_adverse_media' => 'sometimes',
                'is_owner_pep' => 'sometimes',
                'is_controlling_pep' => 'sometimes',
                'is_sanction_match' => 'sometimes',
                'in_fatf' => 'sometimes',
                'transaction_pattern' => 'sometimes',
                'transaction_activities' => 'sometimes',
                'transaction_volume' => 'sometimes',
                'is_owner_high_risk' => 'sometimes',
            ];

            $rules = array_merge($rules, $entityRules);
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! auth()->user()->can(PermissionsEnum::AMLList)) {
                $validator->errors()->add('error', 'You don\'t have permission to edit this section.');

                return;
            }

            $quoteTypeEnum = QuoteTypes::getName(request()->quote_type_id);
            if (! $quoteTypeEnum) {
                $validator->errors()->add('quote_type_id', 'Invalid quote type provided for AML Screening');

                return;
            }

            $quoteType = $quoteTypeEnum->value;
            $quote = $this->getQuoteObjectBy($quoteType, request()->quote_uuid, 'uuid');
            if (! $quote) {
                $validator->errors()->add('quote_uuid', 'Quote not found');

                return;
            }

            $paymentDetails = Payment::with('insuranceProvider')->where([
                'paymentable_type' => $quote->getMorphClass(),
                'paymentable_id' => $quote->id,
            ])->first();

            if (
                $quoteType == QuoteTypes::CAR->value &&
                $paymentDetails?->insuranceProvider?->code == InsuranceProvidersEnum::RSA &&
                $quote->registration_type == CarRegistrationType::PERSONAL
            ) {
                $vehicleDriverDetail = $quote->vehicleDriverDetail;
                if (! $vehicleDriverDetail?->rta_transaction_type) {
                    $validator->errors()->add('error', 'RTA Transaction Type is required for Liva Insurance');
                }
            }
        });
    }
}
