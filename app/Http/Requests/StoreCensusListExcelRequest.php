<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCensusListExcelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quote_uuid' => ['required', 'string', 'max:64'],
            'members' => ['required', 'array', 'min:1'],
            'members.*.full_name' => ['required', 'string', 'max:255'],
            'members.*.date_of_birth' => ['required', 'date_format:Y-m-d'],
            'members.*.gender' => ['required', 'string', 'max:100'],
            'members.*.marital_status' => ['required', 'string', 'max:100'],
            'members.*.relation' => ['required', 'string', 'max:100'],
            'members.*.nationality' => ['required', 'string', 'max:500'],
            'members.*.emirates_of_visa' => ['required', 'string', 'max:100'],
            'members.*.salary' => ['required', 'string', 'max:255'],
            'members.*.category' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quote_uuid.required' => 'Quote UUID is required.',
            'members.required' => 'At least one census member is required.',
            'members.min' => 'At least one census member is required.',
            'members.*.full_name.required' => 'Each member must have a full name.',
            'members.*.date_of_birth.required' => 'Each member must have a date of birth.',
            'members.*.date_of_birth.date_format' => 'Each member date of birth must be in Y-m-d format.',
            'members.*.gender.required' => 'Each member must have a gender.',
            'members.*.marital_status.required' => 'Each member must have a marital status.',
            'members.*.relation.required' => 'Each member must have a relation.',
            'members.*.nationality.required' => 'Each member must have a nationality.',
            'members.*.emirates_of_visa.required' => 'Each member must have an emirate of visa.',
            'members.*.salary.required' => 'Each member must have a salary band or label.',
            'members.*.category.required' => 'Each member must have a category.',
        ];
    }
}
