<?php

namespace App\Http\Requests;

use App\Enums\EaModelEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
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
                function ($attribute, $value, $fail) use ($isCollaborate, $collaborateForbiddenLobs) {
                    if (! QuoteTypes::getName((int) $value)) {
                        $fail('The selected line of business is invalid.');

                        return;
                    }
                    if ((int) $value === QuoteTypeId::HomeAppliance) {
                        $fail('Home Appliance Warranty is not available for the Expert Advisor model.');

                        return;
                    }
                    if ($isCollaborate && in_array((int) $value, $collaborateForbiddenLobs)) {
                        $fail('The collaborate model is not available for the selected LOB.');
                    }
                },
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:150'],
            'mobile_no' => [
                'required',
                'string',
                'max:20',
                function ($_attribute, $value, $fail) {
                    if (! preg_match('/^[+]?[0-9\s\-\(\)]+$/', $value)) {
                        $fail('Phone number format is invalid. Only digits, spaces, +, -, and parentheses are allowed.');

                        return;
                    }
                    if (strlen(preg_replace('/[^0-9]/', '', $value)) < 7) {
                        $fail('Phone number must be at least 7 digits long.');
                    }
                },
            ],
            'business_type_of_insurance_id' => [
                Rule::requiredIf($quoteTypeId === QuoteTypeId::Corpline),
                'nullable',
                'integer',
                'exists:business_type_of_insurance,id',
            ],
            'health_plan_type_id' => [
                Rule::requiredIf($quoteTypeId === QuoteTypeId::Health && ! $isCollaborate),
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
        $isAnyManager = $this->user()->roles->contains(
            fn ($role) => str_contains(strtolower($role->name), 'manager')
        );
        if ($isAnyManager && ! $this->user()->hasRole([RolesEnum::Admin, RolesEnum::Engineering])) {
            $this->merge(['ea_model' => 'referral']);
        }

        $user = $this->user();
        $hasCollaboratePermission = $user->hasPermissionTo(PermissionsEnum::EaCollaborate);

        if ($this->getEaModel() === EaModelEnum::Collaborate && ! $hasCollaboratePermission) {
            $this->merge(['ea_model' => 'referral']);
        }

        // Health, Car, Travel are always excluded from Collaborative (FRD D4).
        // Life and GM are excluded unless the user has RM_ADVISOR (FRD D2).
        // HomeAppliance (HAW) is excluded from all EA models until further notice.
        $this->collaborateForbiddenLobs = [QuoteTypeId::Car, QuoteTypeId::Travel, QuoteTypeId::Health, QuoteTypeId::HomeAppliance];

        if (! $this->user()->hasRole(RolesEnum::RMAdvisor)) {
            $this->collaborateForbiddenLobs[] = QuoteTypeId::Life;
            $this->collaborateForbiddenLobs[] = QuoteTypeId::GroupMedical;
        }
    }

    public function getEaModel()
    {
        return $this->enum('ea_model', EaModelEnum::class);
    }
}
