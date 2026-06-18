<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AssignPreQualificationAdvisorRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('modelType')) {
            $this->merge([
                'modelType' => strtolower((string) $this->input('modelType')),
            ]);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        return $user->can(PermissionsEnum::ASSIGN_GROUP_MEDICAL_PRE_QUALIFICATION_ADVISOR)
            || $user->hasAnyRole([RolesEnum::Admin, RolesEnum::Engineering, RolesEnum::LeadPool]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pq_advisor_id' => 'required|integer|exists:users,id',
            'assigned_lead_id' => 'required|string',
            'modelType' => 'required|string|in:business,health,group_medical',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pq_advisor_id.required' => 'Please select a Pre‑Qualification Advisor.',
            'pq_advisor_id.exists' => 'The selected Pre‑Qualification Advisor does not exist.',
            'assigned_lead_id.required' => 'Lead ID is required for assignment.',
            'modelType.required' => 'Model type is required for assignment.',
            'modelType.in' => 'Pre‑Qualification Advisor assignment is only available for Corpline and Group Medical leads.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'pq_advisor_id' => 'Pre‑Qualification Advisor',
            'assigned_lead_id' => 'lead ID',
            'modelType' => 'model type',
        ];
    }
}
