<?php

namespace App\Http\Requests;

use App\Enums\quoteTypeCode;
use Illuminate\Foundation\Http\FormRequest;

class PlanPdfRequest extends FormRequest
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
        $this->replace(array_merge($this->all(), ['plan_pdf_ids' => explode(',', $this->plan_pdf_ids)]));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'quote_pdf_uuid' => 'required|exists:car_quote_request,uuid',
            'plan_pdf_ids' => 'required|array|max:6',
        ];
    }

    /**
     * validate quote record and maximum number of alread uploaded files
     *
     * @param $validator
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            //validate car quote type
            if (empty(request()->quoteType) || ucwords(request()->quoteType) != quoteTypeCode::Car) {
                $validator->errors()->add('type', 'Invalid quote type provided');
            }
        });
    }

    public function messages()
    {
        return [
            'plan_pdf_ids.max' => 'Maximum 6 plans are allowed to select',
        ];
    }
}
