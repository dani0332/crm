<?php

namespace App\Http\Requests;

use App\Enums\AMLStatusCode;
use Illuminate\Foundation\Http\FormRequest;

class SkipBridgerScreeningRequest extends FormRequest
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
            'quote_type_id' => 'required',
            'quote_type_code' => 'required|string',
            'quote_request_id' => 'required',
            'quote_uuid' => 'required',
            'current_aml_status' => 'required',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            if (request()->current_aml_status !== AMLStatusCode::AMLScreeningFailed) {
                $validator->errors()->add('error', 'AML Screening should be failed to execute skip bridger process');
            }
        });
    }
}
