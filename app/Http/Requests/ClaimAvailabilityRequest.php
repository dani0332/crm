<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClaimAvailabilityRequest extends FormRequest
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
            'items' => 'required|array',
            'items.*.userId' => 'required|exists:users,id',
            'items.*.reason' => 'sometimes',
            'items.*.id' => 'required|exists:claims_lead_allocation_config,id',
            'items.*.max_capacity' => 'sometimes|integer',
        ];
    }
}
