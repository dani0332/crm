<?php

namespace App\Http\Requests;

use App\Services\SageApiService;
use Illuminate\Foundation\Http\FormRequest;

class BookBulkPoliciesRequest extends FormRequest
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
            'model_type' => 'required|string',
            'selectedQuoteIds' => 'required|array|min:1',
        ];
    }
    public function withValidator($validator)
    {
        // check sage is enabled or not
        if (! (new SageApiService)->isSageEnabled()) {
            return response()->json(['errors' => [
                'message' => 'Sage is not enabled',
            ]], 403);
        }
    }

    public function messages()
    {
        return [
            'model_type.required' => 'Quote Type is required.',
            'selectedQuoteIds.required' => 'Select at least one Quote',
            'selectedQuoteIds.min' => 'At least One Quote must be selected',
        ];
    }
}
