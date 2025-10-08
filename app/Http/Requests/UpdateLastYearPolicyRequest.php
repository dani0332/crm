<?php

namespace App\Http\Requests;

use App\Enums\PermissionsEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateLastYearPolicyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user has permission to edit last year details
        return Auth::user()?->can(PermissionsEnum::EDIT_LAST_YEAR_DETAILS) ?? false;
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
            'quote_id' => 'required|integer',
            'renewal_batch' => 'nullable|string|max:255',
            'previous_policy_expiry_date' => 'nullable|date',
            'previous_policy_start_date' => 'nullable|date|before_or_equal:previous_policy_expiry_date',
            'previous_quote_policy_number' => 'nullable|string|max:255',
            'previous_quote_policy_premium' => 'nullable|numeric|min:0',
            'previous_advisor_id' => 'nullable|integer|exists:users,id',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'previous_policy_start_date.before_or_equal' => 'The previous policy start date must be before or equal to the expiry date.',
            'previous_quote_policy_premium.numeric' => 'The previous policy premium must be a valid number.',
            'previous_quote_policy_premium.min' => 'The previous policy premium must be greater than or equal to 0.',
            'previous_advisor_id.exists' => 'The selected previous advisor does not exist.',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'model_type' => 'model type',
            'quote_id' => 'quote ID',
            'renewal_batch' => 'renewal batch',
            'previous_policy_expiry_date' => 'previous policy expiry date',
            'previous_policy_start_date' => 'previous policy start date',
            'previous_quote_policy_number' => 'previous policy number',
            'previous_quote_policy_premium' => 'previous policy premium',
            'previous_advisor_id' => 'previous advisor',
        ];
    }
}
