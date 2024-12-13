<?php

namespace App\Http\Requests;

use App\Enums\SendUpdateLogStatusEnum;
use App\Models\SendUpdateLog;
use Illuminate\Foundation\Http\FormRequest;

class SavePolicyDetailsRequest extends FormRequest
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
        $sendUpdate = SendUpdateLog::find($this->id);

        $rules = [
            'first_name' => 'required|string|max:60',
            'last_name' => 'required|string|max:60',
            'insurance_provider_id' => 'sometimes|nullable|integer',
            'provider_name' => 'nullable|string',
            'plan_id' => 'sometimes|nullable|integer',
            'policy_number' => 'nullable|string|max:60',
            'issuance_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'insurer_quote_number' => 'nullable|string',
            'issuance_status_id' => 'nullable|integer',
            'id' => 'nullable|integer',
            'quote_type' => 'nullable|string',
        ];

        // if category CPD or option is PPE under EF.
        if ($sendUpdate->category->code == SendUpdateLogStatusEnum::CPD || $sendUpdate?->option?->code == SendUpdateLogStatusEnum::PPE) {
            $rules['expiry_date'] = 'required|date|after:start_date';
        }

        return $rules;
    }
}
