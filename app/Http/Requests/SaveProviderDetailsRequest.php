<?php

namespace App\Http\Requests;

use App\Enums\SendUpdateLogStatusEnum;
use App\Models\SendUpdateLog;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveProviderDetailsRequest extends FormRequest
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
            'insurance_provider_id' => 'required|integer',
            'send_update_log_id' => 'required|integer',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $sendUpdate = SendUpdateLog::find($this->send_update_log_id);

            if ($sendUpdate?->category?->code == SendUpdateLogStatusEnum::CPD) {
                $validator->errors()->add('error', 'Provider is fixed based on the original policy and cannot be changed in a correction request.');
            }
        });
    }
}
