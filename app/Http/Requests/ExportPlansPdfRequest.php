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
        $this->replace(array_merge($this->all(), ['plan_ids' => explode(',', $this->plan_ids)]));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'quote_uuid' => ['required', new ValidateQuoteObject],
            'plan_ids' => 'required|array|min:3|max:6',
        ];
    }

    /**
     * allowed for car quote only
     * @param $validator
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (empty(request()->quoteType) || ucwords(request()->quoteType) != quoteTypeCode::Car) {
                $validator->errors()->add('type', 'Invalid quote type provided');
            }
        });
    }

    public function messages()
    {
        return [
            'plan_ids.max' => 'Maximum 6 plans are allowed to select',
            'plan_ids.min' => 'Minimum 3 plans should be selected',
        ];
    }
}
