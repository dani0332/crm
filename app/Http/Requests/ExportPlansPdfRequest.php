<?php

namespace App\Http\Requests;

use App\Enums\quoteTypeCode;
use App\Rules\ValidateQuoteObject;
use Illuminate\Foundation\Http\FormRequest;

class ExportPlansPdfRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function prepareForValidation()
    {
        if (is_string($this->plan_ids)) {
            $this->replace(array_merge($this->all(), ['plan_ids' => explode(',', $this->plan_ids)]));
        }
    }

    /**
     * quoteType, quoteUuid, and PlanIds are required to run this feature
     * this request validation is in use to export pdf from IMCRM form and API endPoint.
     *
     * @return array
     */
    public function rules()
    {
        $quoteType = ucfirst(request()->quoteType);
        $rules = [
            'quote_uuid' => ['required', new ValidateQuoteObject],
            // 'plan_ids' => (request()->quoteType == 'travel' || $quoteType == quoteTypeCode::Health) ? 'required|array|min:1|max:5' : 'required|array|min:3|max:5',
            'plan_ids' => 'required|array|min:1|max:5',
            'addons' => 'nullable|array',
            'hasAdultAndSeniorMember' => 'nullable',
            'selectedPlanIds' => 'nullable|array',
        ];

        return $rules;
    }

    /**
     * allowed for car quote only.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (empty(request()->quoteType) || ! in_array(ucwords(request()->quoteType), [quoteTypeCode::Car, quoteTypeCode::Health, quoteTypeCode::Travel, quoteTypeCode::Bike])) {
                $validator->errors()->add('type', 'Invalid quote type provided');
            }
        });
    }

    /**
     * @return string[]
     */
    public function messages()
    {
        return [
            'plan_ids.max' => 'Maximum 5 plans are allowed to select',
            'plan_ids.min' => (request()->quoteType = 'travel') ? 'Minimum 1 plan should be selected' : 'Minimum 3 plans should be selected',
        ];
    }
}
