<?php

namespace App\Http\Requests;

use App\Enums\SendUpdateLogStatusEnum;
use Illuminate\Foundation\Http\FormRequest;

class UpdateToCustomerRequest extends FormRequest
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
        $rules = [
            'sendUpdateId' => 'required|exists:send_update_logs,id',
            'quoteType' => 'string',
            'action' => 'string',
            'quoteUuid' => 'string',
            'quoteRefId' => 'integer',
            'paymentValidated' => 'boolean',
            'inslyMigrated' => 'boolean',
            'isEmailSent' => 'boolean',
        ];

        if (isset($this->action) && $this->action == SendUpdateLogStatusEnum::ACTION_SNBU) {
            $sendUpdateValidationRequest = new SendUpdateValidationRequest;

            $rules = array_merge(
                $rules,
                $sendUpdateValidationRequest->rules(),
            );
        }

        return $rules;
    }

    /**
     * @return void
     */
    public function withValidator($validator)
    {
        if (isset($this->action) && $this->action == SendUpdateLogStatusEnum::ACTION_SNBU) {
            (new SendUpdateValidationRequest)->withValidator($validator);
        }
    }
}
