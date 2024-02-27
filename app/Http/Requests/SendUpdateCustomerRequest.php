<?php

namespace App\Http\Requests;

use App\Enums\DocumentTypeCode;
use App\Models\SendUpdateLog;
use Illuminate\Foundation\Http\FormRequest;

class SendUpdateCustomerRequest extends FormRequest
{
    protected $sendUpdateDocuemnts;

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
            'sendUpdateId' => 'required|exists:send_update_logs,id',
        ];
    }

    /**
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->sendUpdateDocuemnts = SendUpdateLog::where('id', request()->sendUpdateId ?? '')->firstOrFail()?->documents()->get();
            if ($this->sendUpdateDocuemnts->count()) {
                $this->sendUpdateDocuemnts->filter(function ($document) use ($validator) {
                    if (in_array($document->document_type_code, [DocumentTypeCode::SEND_UPDATE_TAX_INVOICE, DocumentTypeCode::SEND_UPDATE_TAX_INVOICE_RAISED_BUYER])) {
                        $validator->errors()->add('error', 'Send Update already has Tax Invoice document. Please remove the existing document and try again.');
                    }
                });
            }
        });
    }

}
