<?php

namespace App\Http\Requests;

use App\Enums\EaModelEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EALeadCreateRequest extends FormRequest
{
    /** @var array<int> */
    private array $collaborateForbiddenLobs = [];

    public function authorize(): bool
    {
        return $this->user()->hasAnyRole([RolesEnum::EAReferral, RolesEnum::EAManager, RolesEnum::Admin, RolesEnum::Engineering])
            || $this->user()->hasAnyPermission([PermissionsEnum::EaCollaborate]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $isCollaborate = $this->getEaModel() === EaModelEnum::Collaborate;
        $quoteTypeId = (int) $this->input('quote_type_id');
        $collaborateForbiddenLobs = $this->collaborateForbiddenLobs;

        return [
            'ea_model' => ['required', Rule::enum(EaModelEnum::class)],
            'quote_type_id' => [
                'required',
                'integer',
                'exists:quote_type,id',
                function ($attribute, $value, $fail) use ($isCollaborate, $collaborateForbiddenLobs) {
                    if ($isCollaborate && in_array((int) $value, $collaborateForbiddenLobs)) {
                        $fail('The collaborate model is not available for the selected LOB.');
                    }
                },
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:150'],
            'mobile_no' => ['required', 'string', 'max:20'],
            'business_type_of_insurance_id' => [
                Rule::requiredIf($quoteTypeId === QuoteTypeId::Corpline),
                'nullable',
                'integer',
                'exists:business_type_of_insurance,id',
            ],
            'health_plan_type_id' => [
                Rule::requiredIf($quoteTypeId === QuoteTypeId::Health),
                'nullable',
                'integer',
                'exists:health_plan_type,id',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'ea_model.required' => 'Please select an EA model (Referral or Collaborate).',
            'quote_type_id.required' => 'Please select a line of business.',
            'business_type_of_insurance_id.required' => 'Business type is required for Corpline leads.',
            'health_plan_type_id.required' => 'Plan type is required for Health leads.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Manager role can only create Referral model leads
        if ($this->user()->hasRole(RolesEnum::EAManager) && ! $this->user()->hasRole([RolesEnum::Admin, RolesEnum::Engineering])) {
            $this->merge(['ea_model' => 'referral']);
        }

        // Collaborate requires ea-collaborate permission
        $hasCollaboratePermission = $this->user()->hasAnyPermission([PermissionsEnum::EaCollaborate]);

        if ($this->getEaModel() === EaModelEnum::Collaborate && ! $hasCollaboratePermission) {
            $this->merge(['ea_model' => 'referral']);
        }

        // Car, Travel, Health, and GroupMedical are always excluded from collaborate (referral only).
        // Life requires a specific advisor role to use collaborate.
        $this->collaborateForbiddenLobs = [QuoteTypeId::Car, QuoteTypeId::Travel, QuoteTypeId::Health, QuoteTypeId::GroupMedical];

        if (! $this->user()->hasRole(RolesEnum::LifeAdvisor)) {
            $this->collaborateForbiddenLobs[] = QuoteTypeId::Life;
        }
    }

    public function getEaModel()
    {
        return $this->enum($this->input('ea_model'), EaModelEnum::class);
    }
}
