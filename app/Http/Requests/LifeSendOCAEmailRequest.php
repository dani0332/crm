<?php

namespace App\Http\Requests;

use App\Enums\QuoteTypeId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LifeSendOCAEmailRequest extends FormRequest
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
            'quote_uuid' => [
                'required',
                'string',
                Rule::exists('personal_quotes', 'uuid')->where(function ($query) {
                    $query->where('quote_type_id', QuoteTypeId::Life);
                }),
            ],
            'plan_ids' => 'nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'quote_uuid.required' => 'Quote UUID is required',
            'quote_uuid.string' => 'Quote UUID must be a string',
            'quote_uuid.exists' => 'The provided Quote UUID is invalid',
        ];
    }
}
