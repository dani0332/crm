<?php

namespace App\Http\Requests;

use App\Enums\DocumentTypeCategory;
use App\Enums\DocumentTypeCode;
use App\Enums\DocumentTypeEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Models\DocumentType;
use App\Models\SendUpdateLog;
use App\Rules\CustomFileType;
use App\Rules\ValidateBase64;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Foundation\Http\FormRequest;

// This validation belongs to IMCRM Side document upload
class QuotesDocumentRequest extends FormRequest
{
    use GenericQueriesAllLobs;

    protected $documentType;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'files' => ['required', 'array'],
            'files.*' => ['required', 'file'],
            'document_type_code' => 'required|exists:document_types,code,is_active,1',
            'quote_id' => 'required',
            'quote_uuid' => 'required',
        ];

        if (! empty(request()->document_type_code) && ($this->documentType = DocumentType::where('code', request()->document_type_code)->where('quote_type_id', request()->quote_type_id ?? 0)->first())) {
            $rules['files.*'][] = new CustomFileType($this->documentType->accepted_files);
            $rules['files.*'][] = 'max:'.($this->documentType->max_size * 1024);
        }

        if (! empty(request()->document_type_code) && ($this->documentType = DocumentType::where('code', request()->document_type_code)->first())) {
            if (! (request()->is_base_64)) {
                $rules['files.*'] = 'mimes:'.(str_replace('.', '', $this->documentType->accepted_files)).'|max:'.($this->documentType->max_size * 1024);
            }
        }

        if (request()->is_base_64) {
            $rules['files.*'] = ['required', new ValidateBase64($this->documentType)];
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (! request()->filled('quote_id')) {
                return;
            }

            $this->validateLockedQuoteStatus($validator);
            // Check member detail id with category (quote or member)
            if (request()->category == DocumentTypeCategory::MEMBER && request()->member_detail_id == 0) {
                $validator->errors()->add('member_detail_id', 'Member is required for member document');
            }

            $uploadedDocuments = 0;
            $newFilesCount = count(request()->file('files') ?? []);

            if (request()->is_send_update) {
                $this->validateSendUpdate($validator, $uploadedDocuments);
            } else {
                $this->validateQuoteUpload($validator, $uploadedDocuments);
            }

            // Check if total files (existing + new) exceed maximum limit
            if ($this->documentType && ($uploadedDocuments + $newFilesCount > $this->documentType->max_files)) {
                $validator->errors()->add(
                    'error',
                    "You can only upload a maximum of {$this->documentType->max_files} files for ({$this->documentType->text}). ".
                    "Currently {$uploadedDocuments} files exist, and you're trying to upload {$newFilesCount} more."
                );
            }
        });
    }

    protected function validateLockedQuoteStatus($validator)
    {
        // for safe side, if UI is not refreshed, then the issuance documents tab will be enabled, so we need to check the quote status here as well.
        $quote = $this->getQuoteObject(request()->quote_type, request()->quote_id);
        $quoteStatusId = $quote?->quote_status_id ?? null;

        if (
            request()->document_type_key === DocumentTypeEnum::ISSUING_DOCUMENTS &&
            $quoteStatusId &&
            in_array($quoteStatusId, [
                QuoteStatusEnum::PolicyBooked,
                QuoteStatusEnum::POLICY_BOOKING_QUEUED,
                QuoteStatusEnum::POLICY_BOOKING_FAILED,
            ])
        ) {
            if ($quoteStatusId == QuoteStatusEnum::PolicyBooked) {
                $status = 'booked';
            } elseif ($quoteStatusId == QuoteStatusEnum::POLICY_BOOKING_QUEUED) {
                $status = 'in queued';
            } elseif ($quoteStatusId == QuoteStatusEnum::POLICY_BOOKING_FAILED) {
                $status = 'failed';
            }

            $validator->errors()->add('error', "Issuing Document uploads are not allowed after policy is {$status}. Please use Send Update (CPU) to upload additional issuing documents.");
        }
    }

    protected function validateSendUpdate($validator, &$uploadedDocuments)
    {
        $whereFilter = ['document_type_code' => request()->document_type_code];

        // Apply member detail id if provided (for health, later can work for any other lob)
        if (request()->member_detail_id) {
            $whereFilter['member_detail_id'] = request()->member_detail_id;
        }

        $quoteDocuments = SendUpdateLog::where('id', request()->send_update_id ?? '')->first();
        $uploadedDocuments = $quoteDocuments?->documents()->where($whereFilter)->count() ?? 0;

        if (request()->document_type_code == DocumentTypeCode::SEND_UPDATE_AUDIT_RECORD && ! auth()->user()->can(PermissionsEnum::AUDITDOCUMENT_UPLOAD)) {
            $validator->errors()->add('error', 'This section is for audit purposes only. Only authorised users can upload files here');
        }
    }

    protected function validateQuoteUpload($validator, &$uploadedDocuments)
    {
        $quote = $this->getQuoteObjectBy(request()->folder_path ?? '', request()->quote_id);

        if ($this->shouldValidateMember($quote)) {
            $this->validateMemberDetails($validator, $quote);
        }

        if (! empty($quote)) {
            $uploadedDocuments = $quote->documents()->where('document_type_code', request()->document_type_code);

            // Apply member detail id if provided (for health, later can work for any other lob)
            if (request()->member_detail_id) {
                $uploadedDocuments = $uploadedDocuments->where('member_detail_id', request()->member_detail_id);
            }

            $uploadedDocuments = $uploadedDocuments->count();
        }
    }

    protected function shouldValidateMember($quote)
    {
        return in_array(ucfirst(request()->quoteType), [quoteTypeCode::Health, quoteTypeCode::Travel]) &&
            isset($quote->id) &&
            ! empty(request()->member_detail_id);
    }

    protected function validateMemberDetails($validator, $quote)
    {
        if (! in_array(ucfirst(request()->quoteType), [quoteTypeCode::Health, quoteTypeCode::Travel]) &&
            ! empty(request()->member_detail_id)) {
            $validator->errors()->add('member_detail_id', 'Member can be attached only for Health or Travel Insurance type');

            return;
        }

        $memberExists = $quote->customerMembers()->where('id', request('member_detail_id'))->exists();
        if (! $memberExists) {
            $validator->errors()->add('member_detail_id', 'Invalid member detail id provided');
        }
    }
}
