<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PermissionsEnum;
use App\Models\DocumentType;
use App\Models\ClaimRequest;
use App\Rules\CustomFileType;
use App\Rules\ValidateBase64;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClaimDocumentRequest extends FormRequest
{
    protected $documentType;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'files' => ['required', 'array'],
            'files.*' => ['required', 'file'],
            'document_type_code' => [
                'required',
                'string',
                Rule::exists('document_types', 'code')->where('is_active', 1)
            ],
            'folder_path' => ['nullable', 'string', 'max:255'],
        ];

        // Get document type for validation
        if ($this->filled('document_type_code')) {
            $this->documentType = DocumentType::where('code', $this->document_type_code)
                ->where('is_active', 1)
                ->first();

            if ($this->documentType) {
                // Add file type and size validation based on document type
                $rules['files.*'][] = new CustomFileType($this->documentType->accepted_files);
                $rules['files.*'][] = 'max:' . ($this->documentType->max_size * 1024); // Convert MB to KB
            }
        }

        // Handle base64 file uploads
        if ($this->boolean('is_base_64')) {
            $rules['files.*'] = ['required', new ValidateBase64($this->documentType)];
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'files' => 'documents',
            'files.*' => 'document',
            'document_type_code' => 'document type',
            'folder_path' => 'folder path',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'files.required' => 'At least one document is required.',
            'files.array' => 'Documents must be provided as an array.',
            'files.*.required' => 'Each document file is required.',
            'files.*.file' => 'Each document must be a valid file.',
            'document_type_code.required' => 'Document type is required.',
            'document_type_code.exists' => 'Selected document type is invalid or inactive.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->filled('document_type_code') || !$this->documentType) {
                return;
            }

            $claim = $this->route('claim');
            if (!$claim instanceof ClaimRequest) {
                $validator->errors()->add('claim', 'Invalid claim provided.');
                return;
            }

            // Count existing documents of this type
            $existingDocuments = $claim->documents()
                ->where('document_type_code', $this->document_type_code)
                ->count();

            $newFilesCount = count($this->file('files', []));
            $totalFiles = $existingDocuments + $newFilesCount;

            // Check if total files exceed maximum limit
            if ($totalFiles > $this->documentType->max_files) {
                $validator->errors()->add(
                    'files',
                    "You can only upload a maximum of {$this->documentType->max_files} files for ({$this->documentType->text}). " .
                    "Currently {$existingDocuments} files exist, and you're trying to upload {$newFilesCount} more."
                );
            }

            // Check permissions for specific document types
            $this->validatePermissions($validator);
        });
    }

    /**
     * Validate specific document type permissions.
     */
    protected function validatePermissions($validator): void
    {
        // Add any claim-specific document type permission checks here
        // For example, if certain document types require special permissions
        
        // Example: Audit documents require special permission
        if ($this->document_type_code === 'CLAIM_AUDIT_RECORD' && 
            (!auth()->user() || !auth()->user()->can(PermissionsEnum::CLAIM_DOCUMENT_UPLOAD))) {
            $validator->errors()->add(
                'document_type_code',
                'You do not have permission to upload this type of document.'
            );
        }
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'folder_path' => $this->folder_path ?: 'claims',
        ]);
    }
}
