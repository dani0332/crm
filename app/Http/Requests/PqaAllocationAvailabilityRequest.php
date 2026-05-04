<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PqaAllocationAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'items' => 'required|array',
            'items.*.userId' => 'required|exists:users,id',
            'items.*.reason' => 'sometimes',
            'items.*.id' => 'required|exists:pqa_lead_allocation_config,id',
            'items.*.max_capacity' => 'sometimes|integer',
            'items.*.maxCap' => 'sometimes|integer',
            'items.*.resetCap' => 'sometimes|boolean',
        ];
    }
}
