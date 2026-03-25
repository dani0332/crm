<?php

namespace App\Http\Requests;

use App\Enums\QuoteTypeId;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignLeadRequest extends FormRequest
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
        return [
            'quoteUUID' => ['required'],
            'quoteTypeId' => ['required', Rule::in(QuoteTypeId::asArray())],
            'reAssignAdvisor' => ['sometimes', 'boolean'],
            'triggerOCB' => ['sometimes', 'boolean'],
            'teamId' => ['sometimes', 'nullable'],
            'sicAdvisorRequested' => ['sometimes', 'boolean'],
        ];
    }
}
