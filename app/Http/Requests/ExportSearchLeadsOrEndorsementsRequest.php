<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportSearchLeadsOrEndorsementsRequest extends FormRequest
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
            'list' => 'required|string',
            'code' => 'nullable|string',
            'insured_name' => 'nullable|string',
            'member_first_name' => 'nullable|string',
            'member_last_name' => 'nullable|string',
            'company_name' => 'nullable|string',
            'policy_number' => 'nullable|string',
            'mobile_no' => 'nullable|string',
            'email' => 'nullable|string',
            'su_code' => 'nullable|string',
            'date_type' => 'nullable|string',
            'date_range' => 'nullable|array',
            'quote_status' => 'nullable|array',
            'payment_status' => 'nullable|array',
            'line_of_business' => ['nullable', function ($attribute, $value, $fail) {
                if (! is_null($value)) {
                    if (! is_array($value) && ! is_string($value)) {
                        $fail($attribute.' must be either a string or an array');
                    }
                }
            }],
            'business_insurance_type' => 'nullable|array',
            'currently_insured_with' => 'nullable|array',
            'department' => 'nullable|array',
            'advisors' => 'nullable|array',
            'insurer_tax_invoice_number' => 'nullable|string',
            'insurer_commission_tax_invoice_number' => 'nullable|string',
            'update_status' => 'nullable|array',
            'send_update_type' => 'nullable|array',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->checkAutoDateApplyFilters()) {
                $validator->errors()->add('flash', 'Date range is required for the selected filters');
            }

            if (empty(request()->except(['list', 'page', 'sortBy', 'sortType']))) {
                $validator->errors()->add('flash', 'Please select at least one filter before exporting');
            }

            if ($validator->errors()->any()) {
                foreach ($validator->errors()->all() as $error) {
                    $validator->errors()->add('flash', $error);
                }
            }
        });
    }

    protected function checkAutoDateApplyFilters(): bool
    {
        $fieldPresent = false;
        $autoApplyDateRangeFields = [
            'insured_name',
            'member_first_name',
            'member_last_name',
            'company_name',
            'policy_number',
            'quote_status',
            'payment_status',
            'line_of_business',
            'business_insurance_type',
            'currently_insured_with',
            'department',
            'advisors',
            'update_status',
        ];

        if (empty(request()->date_type) || empty(request()->date_range)) {
            foreach ($autoApplyDateRangeFields as $field) {
                if (! empty(request()->{$field})) {
                    $fieldPresent = true;
                    break;
                }
            }
        }

        return $fieldPresent;
    }
}
