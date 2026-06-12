<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UTMReportRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        if (empty($this->all())) {
            return [];
        }

        return [
            'quote_type_id' => 'required',
            'group_by_one' => 'required',
            'date_range' => 'required|array',
            'group_by_two' => 'nullable',
        ];
    }
}
