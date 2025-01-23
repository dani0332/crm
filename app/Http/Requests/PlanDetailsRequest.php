<?php

namespace App\Http\Requests;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use Illuminate\Foundation\Http\FormRequest;

class PlanDetailsRequest extends FormRequest
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
        $rules = [
            'insurance_provider_id' => 'required|integer',
            'price_with_vat' => 'required',
            'insurer_quote_number' => 'nullable',
        ];

        if (request()->quoteType == quoteTypeCode::Life) {
            $rules['price_vat_not_applicable'] = 'required|numeric|regex:/^\d{1,7}(\.\d{1,2})?$/';
        } else {
            $rules['price_vat_applicable'] = 'required|numeric|regex:/^\d{1,7}(\.\d{1,2})?$/';
        }

        if (request()->quoteType == quoteTypeCode::Business) {
            // for business either price_vat_applicable or price_vat_not_applicable is required, and only one field should have value
            $rules['price_vat_applicable'] = 'nullable|required_without:price_vat_not_applicable|numeric|regex:/^\d{1,7}(\.\d{1,2})?$/';
            $rules['price_vat_not_applicable'] = 'nullable|required_without:price_vat_applicable|numeric|regex:/^\d{1,7}(\.\d{1,2})?$/';
        }

        return $rules;
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $repository = getRepositoryObject(request()->quoteType);
            $quoteModel = $repository::where('code', request()->code)->firstOrFail();
            if ($quoteModel && $quoteModel->quote_status_id == QuoteStatusEnum::PolicyBooked) {
                $validator->errors()->add('value', 'No further editing is required as the policy has been booked');
            }

            if ($quoteModel && $quoteModel->quote_status_id == QuoteStatusEnum::POLICY_BOOKING_FAILED && ! auth()->user()->can(PermissionsEnum::BOOKING_FAILED_EDIT)) {
                $validator->errors()->add('error', 'Policy Booking Failed! Please contact finance for correction of details');
            }
        });
    }
}
