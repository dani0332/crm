<?php

namespace App\Http\Requests\Bor;

use Illuminate\Foundation\Http\FormRequest;

class BorSignRequest extends FormRequest
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
            'bor_ref_id' => 'required|string|exists:bor_logs,bor_reference',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
            'is_base_64' => 'nullable|boolean',
            'download_clicked' => 'nullable|boolean',
            'insurer_name' => 'nullable|string|max:255',
            'policy_number' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bor_ref_id.required' => 'BOR reference ID is required.',
            'bor_ref_id.exists' => 'The specified BOR reference does not exist.',
            'file.mimes' => 'The file must be a PDF, JPG, JPEG, or PNG.',
            'file.max' => 'The file size must not exceed 10MB.',
            'insurer_name.max' => 'Insurer name must not exceed 255 characters.',
            'policy_number.max' => 'Policy number must not exceed 255 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert string boolean values to actual booleans
        if ($this->has('is_base_64')) {
            $this->merge([
                'is_base_64' => filter_var($this->input('is_base_64'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            ]);
        }

        if ($this->has('download_clicked')) {
            $this->merge([
                'download_clicked' => filter_var($this->input('download_clicked'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            ]);
        }
    }
}
