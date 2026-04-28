<?php

namespace App\Http\Requests\Bor;

use App\Services\Logger\LoggerService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BorRequest extends FormRequest
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
        $action = $this->route()->getActionMethod();

        // Base rules that apply to BOR actions that use 'bor_ref_id' field
        // Note: uploadDocument uses 'document_category' instead of 'bor_ref_id'
        $baseRules = [
            'bor_ref_id' => 'required|string|exists:bor_logs,bor_reference',
        ];

        // Conditional rules based on the action
        switch ($action) {
            case 'signDocument':
                return array_merge($baseRules, [
                    'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
                    'is_base_64' => 'nullable|boolean',
                    'document_type_code' => 'nullable|string|exists:document_types,code,is_active,1',
                    'download_clicked' => 'nullable|boolean',
                    'insurer_name' => 'nullable|string|max:255',
                    'policy_number' => 'nullable|string|max:255',
                ]);

            case 'deleteDocument':
                return array_merge($baseRules, [
                    'doc_name' => 'required|string|max:255',
                    'doc_uuid' => 'required|string|max:255',
                ]);

            case 'uploadDocument':
                // Note: uploadDocument uses 'document_category' as the BOR reference instead of 'bor_ref_id'
                // This is intentional as this action has a different parameter structure
                return [
                    'file' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240', // 10MB max
                    'quote_type' => 'required|string',
                    'quote_uuid' => 'required|string',
                    'is_base_64' => 'nullable|boolean',
                    'document_type_code' => 'nullable|string|exists:document_types,code,is_active,1',
                    // document_category serves as the BOR reference for this action
                    'document_category' => 'nullable|string|max:255|exists:bor_logs,bor_reference',
                ];

            default:
                return $baseRules;
        }
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $action = $this->route()->getActionMethod();

        // Base messages
        $baseMessages = [
            'bor_ref_id.required' => 'BOR reference ID is required.',
            'bor_ref_id.exists' => 'The specified BOR reference does not exist.',
        ];

        // Conditional messages based on the action
        switch ($action) {
            case 'signDocument':
                return array_merge($baseMessages, [
                    'document_type_code.exists' => 'The specified document type code does not exist.',
                    'file.mimes' => 'The file must be a PDF, JPG, JPEG, or PNG.',
                    'file.max' => 'The file size must not exceed 10MB.',
                    'insurer_name.max' => 'Insurer name must not exceed 255 characters.',
                    'policy_number.max' => 'Policy number must not exceed 255 characters.',
                ]);

            case 'deleteDocument':
                return array_merge($baseMessages, [
                    'doc_name.required' => 'Document name is required.',
                    'doc_name.max' => 'Document name must not exceed 255 characters.',
                    'doc_uuid.required' => 'Document UUID is required.',
                    'doc_uuid.max' => 'Document UUID must not exceed 255 characters.',
                ]);

            case 'uploadDocument':
                return [
                    'file.required' => 'Document file is required.',
                    'file.file' => 'The uploaded document must be a valid file.',
                    'file.mimes' => 'The file must be a PDF, JPG, JPEG, PNG, DOC, or DOCX.',
                    'file.max' => 'The file size must not exceed 10MB.',
                    'quote_type.required' => 'Quote type is required.',
                    'quote_uuid.required' => 'Quote UUID is required.',
                    'document_type_code.exists' => 'The specified document type code does not exist.',
                    'document_category.max' => 'Document category must not exceed 255 characters.',
                    'document_category.exists' => 'The specified BOR reference does not exist.',
                ];

            default:
                return $baseMessages;
        }
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $action = $this->route()->getActionMethod();

        // Apply boolean conversion for signDocument and uploadDocument actions
        if (in_array($action, ['signDocument', 'uploadDocument'])) {
            // Convert string boolean values to actual booleans
            if ($this->has('is_base_64')) {
                $this->merge([
                    'is_base_64' => filter_var($this->input('is_base_64'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
                ]);
            }

            if ($action === 'signDocument' && $this->has('download_clicked')) {
                $this->merge([
                    'download_clicked' => filter_var($this->input('download_clicked'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
                ]);
            }
        }
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        $action = $this->route()->getActionMethod();

        LoggerService::info("BorRequest Validation failed for action: {$action}", [
            'action' => $action,
            'request' => $this->all(),
            'errors' => $validator->errors(),
        ]);

        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
