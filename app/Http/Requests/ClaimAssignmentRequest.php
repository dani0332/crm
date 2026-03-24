<?php

namespace App\Http\Requests;

use App\Enums\QuoteTypeId;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClaimAssignmentRequest extends FormRequest
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
            'claimUUID' => ['required'],
            'quoteTypeId' => ['required', Rule::in(QuoteTypeId::asArray())],
            'quoteTypeLabel' => ['required'],
            'triggerOCB' => ['sometimes', 'boolean'],
        ];
    }
}
